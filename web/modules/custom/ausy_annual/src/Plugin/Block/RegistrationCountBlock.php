<?php

namespace Drupal\ausy_annual\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'AUSY Registration Count block' block.
 *
 * @Block(
 *   id = "ausy_registration_count_block",
 *   admin_label = @Translation("Registration Count block"),
 *   category = @Translation("AUSY blocks")
 * )
 */
class RegistrationCountBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node_query = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery();

    // Get count of all available registrations.
    $query = $node_query
      ->condition('status', 1)
      ->condition('type', 'registration');

    $count = $query->count()->execute();

    // Other modules can alter this count value.
    $this->moduleHandler->alter('ausy_annual_registration_count_block', $count, $node_query, $query);

    $build['content'] = [
      '#type' => 'inline_template',
      '#template' => '<div class="block-filter-text-source">Current count of registrations: {{ count }}</div>',
      '#context' => [
        'count' => $count,
      ],
      '#cache' => [
        'tags' => [
          'node_type:registration',
        ],
      ],
    ];

    return $build;
  }

}
