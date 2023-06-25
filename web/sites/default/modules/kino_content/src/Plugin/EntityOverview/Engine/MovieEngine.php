<?php

namespace Drupal\kino_content\Plugin\EntityOverview\Engine;

use Drupal\entity_overview\Entity\Overview;
use Drupal\entity_overview\OverviewFieldInfoInterface;
use Drupal\entity_overview\OverviewFilter;
use Drupal\kino_content\OverviewFields\ActiveField;
use Drupal\kino_content\OverviewFields\PremiereField;
use Drupal\relewise\Plugin\EntityOverview\Engine\ContentEngine;
use Relewise\Factory\DataValueFactory;
use Relewise\Models\CollectionFilterType;
use Relewise\Models\ContentAttributeSorting;
use Relewise\Models\ContentAttributeSortingSortableAttribute;
use Relewise\Models\ContentDataDoubleRangeFacet;
use Relewise\Models\ContentDataDoubleRangesFacet;
use Relewise\Models\ContentDataDoubleValueFacet;
use Relewise\Models\ContentDataFilter;
use Relewise\Models\ContentDataSorting;
use Relewise\Models\ContentDataStringValueFacet;
use Relewise\Models\ContentFacetQuery;
use Relewise\Models\ContentPopularitySorting;
use Relewise\Models\ContentRelevanceSorting;
use Relewise\Models\ContentSearchRequest;
use Relewise\Models\ContentSearchResponse;
use Relewise\Models\ContentSearchSettings;
use Relewise\Models\ContentSortBySpecification;
use Relewise\Models\ContentSorting;
use Relewise\Models\EqualsCondition;
use Relewise\Models\FacetingField;
use Relewise\Models\FilterCollection;
use Relewise\Models\floatRange;
use Relewise\Models\GreaterThanCondition;
use Relewise\Models\LessThanCondition;
use Relewise\Models\SearchIndexSelector;
use Relewise\Models\SelectedContentPropertiesSettings;
use Relewise\Models\SortOrder;
use Relewise\Models\ValueConditionCollection;

/**
 * @Engine(
 *  id = "relewise_movie",
 *  title = "Relewise Movie",
 *  facets = {
 *    "owner",
 *    "count",
 *    "sort",
 *    "text",
 *    "pagination",
 *    "premiere",
 *    "active"
 *  },
 *  multiple = true,
 *  recommendations = true
 * )
 */
class MovieEngine extends ContentEngine {

  /**
   * @inheritDoc
   */
  protected function getEngineFieldInfo(Overview $overview, string $field): ?OverviewFieldInfoInterface {
    return match ($field) {
      'premiere' => new PremiereField(),
      'active' => new ActiveField(),
      default => parent::getEngineFieldInfo($overview, $field)
    };
  }

  /**
   * @inheritDoc
   */
  protected function getSearchSorting(OverviewFilter $filter): ?ContentSorting {
    $sorting = NULL;
    switch ($filter->getSort()) {
      case 'media_rating':
        $sorting = ContentDataSorting::create('field_media_ratings_score', SortOrder::Descending);
        break;
      case 'user_rating':
        $sorting = ContentDataSorting::create('field_kino_rating_score', SortOrder::Descending);
        break;
      case 'anticipation_rating':
        $sorting = ContentDataSorting::create('field_anticipation_score', SortOrder::Descending);
        break;
      case 'premiere':
        $sorting = ContentDataSorting::create('field_premiere', SortOrder::Descending);
        break;
      case 'newest':
        $sorting = ContentDataSorting::create($filter->getOverview()->getSortField(), SortOrder::Descending);
        break;
      case 'oldest':
        $sorting = ContentDataSorting::create($filter->getOverview()->getSortField(), SortOrder::Ascending);
        break;
      case 'alphabetical':
        $sorting = ContentAttributeSorting::create(ContentAttributeSortingSortableAttribute::DisplayName, SortOrder::Ascending);
        break;
      case 'popular':
        $sorting = ContentPopularitySorting::create();
        break;
      case 'relevant':
        $sorting = ContentRelevanceSorting::create();
        break;
    }
    return $sorting;
  }

  /**
   * @inheritDoc
   */
  public function getSortCriterias(): array {
    return [
      'media_rating' => $this->t('Top media rated'),
      'user_rating' => $this->t('Top user rated'),
      'anticipation_rating' => $this->t('Top anticipated'),
      'relevant' => $this->t('Most relevant'),
      'popular' => $this->t('Most popular'),
      'premiere' => $this->t('Premiere date'),
      'alphabetical' => $this->t('Alphabetical')
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getSearchFields(OverviewFilter $filter): array {
    $fields = [];
    $entity_types = $filter->getOverview()->getEntityBundles();
    $fields['type'] = array_keys($entity_types);
    $fields['bundle'] = [];
    foreach ($entity_types as $entity_type => $bundles) {
      $fields['bundle'] = array_merge($fields['bundle'], $bundles);
    }
    foreach ($filter->getFieldValues() as $key => $value) {
      if (in_array($key, ['text', 'premiere', 'active']) && !empty($value)) {
        $fields[$key] = $value;
      }
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getResult(OverviewFilter $filter) {
    $this->killSwitch->trigger();
    $this->setCount($filter, 0);
    $fields = $this->getSearchFields($filter);
    $sorting = $this->getSearchSorting($filter);
    $term = $this->getSearchTerm($filter);
    $result = $this->searchContent($term, $fields, $filter->getCount() ?? 0, $filter->getPage(), $sorting, $filter->getFieldValue('premiere'), $filter->getFieldValue('active'), $this->getSetting('index'));
    if (!empty($result) && !empty($result->hits) && $result->hits > 0) {
      $this->setCount($filter, $result->hits);
    }

    if ($filter->hasPagination()) {
      $this->request->query->set('page', $filter->getPage());
      $this->pagerManager->createPager($this->getCount($filter), $filter->getCount(), 0);
    }
    return $result;
  }


  /**
   * Performs a search for content in the index.
   *
   * @param string $term
   * @param array $fields
   * @param int $count
   * @param int $page
   * @param \Relewise\Models\ContentSorting|null $sorting
   * @param string $premiere
   * @param bool $active
   * @param string $index
   *
   * @return \Relewise\Models\ContentSearchResponse|null
   */
  public function searchContent(string $term, array $fields, int $count, int $page, ?ContentSorting $sorting, ?string $premiere, ?bool $active, string $index = ''): ?ContentSearchResponse {
    $facets = [];
    foreach ($fields as $key => $value) {
      if (is_array($value)) {
        $facets[] = ContentDataStringValueFacet::create($key, array_values($value), CollectionFilterType::Or)->setField(FacetingField::Data);
      } else {
        $facets[] = ContentDataStringValueFacet::create($key, [$value], CollectionFilterType::Or)->setField(FacetingField::Data);
      }
    }
    $skip = 0;
    if (!empty($count)) {
      $skip = $page * $count;
    }
    if (empty($settings)) {
      $settings = ContentSearchSettings::create();
      $properties = SelectedContentPropertiesSettings::create();
      $properties->setDisplayName(TRUE);
      $properties->setCategoryPaths(FALSE);
      $properties->setAssortments(FALSE);
      $properties->setAllData(FALSE);
      $properties->setViewedByUserInfo(FALSE);
      $settings->setSelectedContentProperties(
        $properties
      );
    }

    $request = ContentSearchRequest::create(
      $this->relewise->getCurrentLanguage(),
      $this->relewise->getCurrentCurrency(),
      $this->relewise->getCurrentUser(),
      $this->relewise->getCurrentTitle(),
      empty($term) ? NULL : $term,
      $skip,
      $count
    );

    $filters = [];
    if (!empty($premiere)) {
      $filter = ContentDataFilter::create(
        'field_premiere'
      );
      if ($premiere == 'past') {
        $filter->setConditions(
          ValueConditionCollection::create()
            ->addToItems(LessThanCondition::create(time()))
        );
      } else {
        $filter->setConditions(
          ValueConditionCollection::create()
            ->addToItems(GreaterThanCondition::create(time()))
        );
      }
      $filters[] = $filter;
    }
    if ($active) {
      $filter = ContentDataFilter::create(
        'active'
      );
      $filter->setConditions(
        ValueConditionCollection::create()
          ->addToItems(EqualsCondition::create(DataValueFactory::boolean($active)))
      );
      $filters[] = $filter;
    }
    if (!empty($filters)) {
      $filterCollection = FilterCollection::create(...$filters);
      $request->setFilters($filterCollection);
    }

    $request->setSettings($settings);
    if (!empty($facets)) {
      $facetQuery = ContentFacetQuery::create();
      foreach ($facets as $facet) {
        $facetQuery->addToItems($facet);
      }
      $request->setFacets($facetQuery);
    }
    if (!empty($sorting)) {
      $specification = ContentSortBySpecification::create()->setValue($sorting);
      $request->setSorting($specification);
    }
    if (!empty($index)) {
      $request->setIndexSelector(SearchIndexSelector::create($index));
    }
    return $this->relewise->getSearcher()->contentSearch($request);
  }
}
