<?php

namespace Drupal\kino_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\jwt\Authentication\Provider\JwtAuth;
use Drupal\jwt_auth_issuer\Controller\JwtAuthIssuerController;
use Drupal\kino_api\Enum\RegisterResultCode;
use Drupal\kino_api\Exception\InternalServerErrorHttpException;
use Drupal\kino_api\Validator\RegisterValidator;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends ControllerBase {

  private JwtAuth $auth;

  public function __construct(JwtAuth $auth) {
    $this->auth = $auth;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jwt.authentication.jwt')
    );
  }

  public function tokenResponse(): JsonResponse {
    $jwtAuthIssuerController = new JwtAuthIssuerController($this->auth);
    return $jwtAuthIssuerController->tokenResponse();
  }

  /**
   * [POST] /transform/user/register
   *
   * Registers a user.
   * See more: https://novicell.atlassian.net/l/cp/dEikWpuM.
   */
  public function register(Request $request): JsonResponse {
    $values = json_decode($request->getContent(), TRUE);

    /** @var RegisterValidator $validator */
    $validator = \Drupal::service(RegisterValidator::class);

    $validator->validateEmptyMail($values['mail']);
    $validator->validateEmptyName($values['field_name']);
    $validator->validateEmptyGender($values['field_gender']);
    $validator->validateEmptyPhoneNumber($values['field_phone_number']);

    $validator->validateExistingMail($values['mail']);
    $validator->validateGender($values['field_gender']);
    $validator->validatePhoneNumber($values['field_phone_number']);

    $account = User::create();

    // This form is used for two cases:
    // - Self-register (route = 'user.register').
    // - Admin-create (route = 'user.admin_create').
    // If the current user has permission to create users then it must be the
    // second case.
    $admin = $account->access('create');

    // Because the user status has security implications, users are blocked by
    // default when created programmatically and need to be actively activated
    // if needed. When administrators create users from the user interface,
    // however, we assume that they should be created as activated by default.
    if ($admin || \Drupal::config('user.settings')
        ->get('register') == UserInterface::REGISTER_VISITORS) {
      $account->activate();
    }

    $pass = (!\Drupal::config('user.settings')->get('verify_mail') || $admin)
      ? $values['pass']
      : \Drupal::service('password_generator')->generate();

    $values['pass'] = $pass;
    $values['init'] = $values['mail'];
    if ($admin) {
      $notify = !empty($values['notify']);
    }
    else {
      $notify = FALSE;
    }

    /** @var EntityFieldManagerInterface $entityFieldManager */
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $base_fields = array_keys($entityFieldManager->getBaseFieldDefinitions('user'));
    foreach ($account->getFields() as $field_name => $field) {
      if (in_array($field_name, ['name', 'pass', 'mail', 'init', 'timezone'])) {
        if (isset($values[$field_name])) {
          $account->set($field_name, $values[$field_name]);
        }
      }
      elseif (in_array($field_name, $base_fields)) {
        continue;
      }
      else {
        if (isset($values[$field_name])) {
          $account->set($field_name, $values[$field_name]);
        }
      }
    }
    $account->setChangedTime(\Drupal::service('datetime.time')
      ->getRequestTime());

    // Set the username based on the email address and save the account.
    email_registration_user_insert($account);


    \Drupal::logger('user')
      ->notice('New user: %name %email.', [
        '%name' => $account->getAccountName(),
        '%email' => '<' . $account->getEmail() . '>',
        'type' => $account->toLink($this->t('Edit'), 'edit-form')->toString(),
      ]);

    // Add plain text password into user account to generate mail tokens.
    $account->password = $pass;

    // New administrative account without notification.
    if ($admin && !$notify) {
      return $this->createJsonResponse(
        $this->t('Created a new user account for <a href=":url">%name</a>. No email has been sent.', [
          ':url' => $account->toUrl()->toString(),
          '%name' => $account->getAccountName(),
        ]),
        RegisterResultCode::$ADMIN_CREATED_NO_MAIL
      );
    }
    // No email verification required; log in user immediately.
    elseif (!$admin && !\Drupal::config('user.settings')
        ->get('verify_mail') && $account->isActive()) {
      _user_mail_notify(RegisterResultCode::$NO_APPROVAL_REQUIRED, $account);
      user_login_finalize($account);
      return $this->createJsonResponse(
        $this->t('Registration successful. You are now logged in.'),
        RegisterResultCode::$NO_APPROVAL_REQUIRED,
      );
    }
    // No administrator approval required.
    elseif ($account->isActive() || $notify) {
      if (!$account->getEmail() && $notify) {
        return $this->createJsonResponse(
          $this->t('The new user <a href=":url">%name</a> was created without an email address, so no welcome message was sent.', [
            ':url' => $account->toUrl()->toString(),
            '%name' => $account->getAccountName(),
          ]),
          RegisterResultCode::$WITHOUT_EMAIL,
        );
      }
      else {
        $op = $notify ? RegisterResultCode::$ADMIN_CREATED : RegisterResultCode::$NO_APPROVAL_REQUIRED;
        if (_user_mail_notify($op, $account)) {
          if ($notify) {
            return $this->createJsonResponse(
              $this->t('A welcome message with further instructions has been emailed to the new user <a href=":url">%name</a>.', [
                ':url' => $account->toUrl()->toString(),
                '%name' => $account->getAccountName(),
              ]),
              RegisterResultCode::$ADMIN_CREATED);
          }
          else {
            return $this->createJsonResponse(
              $this->t('A welcome message with further instructions has been sent to your email address.'),
              RegisterResultCode::$PENDING_EMAIL_VERIFICATION
            );
          }
        }
        else {
          throw new InternalServerErrorHttpException(
            $this->t('Unable to send email. Contact the site administrator if the problem persists.'),
            RegisterResultCode::$CANT_SEND_MAIL,
          );
        }
      }
    }
    // Administrator approval required.
    else {
      _user_mail_notify(RegisterResultCode::$PENDING_APPROVAL, $account);
      return $this->createJsonResponse(
        $this->t('Thank you for applying for an account. Your account is currently pending approval by the site administrator.<br />In the meantime, a welcome message with further instructions has been sent to your email address.'),
        RegisterResultCode::$PENDING_APPROVAL
      );
    }
  }

  /**
   * Create a success JsonResponse with message and result code.
   */
  private function createJsonResponse(string $message, string $resultCode): JsonResponse {
    return new JsonResponse([
      'status' => $message,
      'resultCode' => $resultCode,
    ]);
  }

  public function orders(User $user) {
    return [];
  }

  public function currentOrders() {
    return $this->redirect('kino_api.user.orders', ['user' => $this->currentUser()->id()]);
  }

  /**
   * [PUT] /api/user/{user}/edit
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\user\Entity\User $user
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function editUser(Request $request, User $user): JsonResponse {
    $values = json_decode($request->getContent(), TRUE);
    if (isset($values['field_address'])) {
      $user->set('field_address', $values['field_address']);
    }
    if (isset($values['field_alias'])) {
      $user->set('field_alias', $values['field_alias']);
    }
    if (isset($values['field_birthday'])) {
      $user->set('field_birthday', $values['field_birthday']);
    }
    if (isset($values['field_city'])) {
      $user->set('field_city', $values['field_city']);
    }
    if (isset($values['field_gender'])) {
      $user->set('field_gender', $values['field_gender']);
    }
    if (isset($values['field_name'])) {
      $user->set('field_name', $values['field_name']);
    }
    if (isset($values['field_newsletter'])) {
      $user->set('field_newsletter', $values['field_newsletter']);
    }
    if (isset($values['field_zip_code'])) {
      $user->set('field_zip_code', $values['field_zip_code']);
    }
    if (isset($values['field_phone_number'])) {
      $user->set('field_phone_number', $values['field_phone_number']);
    }
    $user->save();

    return JsonResponse::create(['message' => $this->t('User profile has been updated.')]);
  }

  /**
   * [PUT] /api/user/{user}/changepw
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \Drupal\user\Entity\User $user
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function changePassword(Request $request, User $user): JsonResponse {
    $values = json_decode($request->getContent(), TRUE);
    if (empty($values['old_password'])) {
      return JsonResponse::create(['error' => $this->t("Your current password is missing or incorrect.")], Response::HTTP_BAD_REQUEST);
    }
    if (empty($values['new_password'])) {
      return JsonResponse::create(['error' => $this->t('@name field is required.', ['@name' => $this->t('New password')])], Response::HTTP_BAD_REQUEST);
    }
    /** @var \Drupal\Core\Password\PasswordInterface $password_hasher */
    $passwordHasher = \Drupal::service('password');
    if ($passwordHasher->check($values['old_password'], $user->getPassword())) {
      $user->setPassword($values['new_password']);
      $user->save();
    } else {
      return JsonResponse::create(['error' => $this->t("Your current password is missing or incorrect.")], Response::HTTP_BAD_REQUEST);
    }

    return JsonResponse::create(['message' => $this->t('User profile has been updated.')]);
  }

}
