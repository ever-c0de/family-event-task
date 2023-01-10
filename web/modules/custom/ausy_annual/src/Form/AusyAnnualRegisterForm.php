<?php

namespace Drupal\ausy_annual\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Subscribe to the newsletter form.
 */
class AusyAnnualRegisterForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a new AusyAnnualRegisterForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ausy_annual_register_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
//    \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create([
//      'name' => $department,
//      'vid' => $vid,
//    ])->save();

    $form['employee_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of the employee'),
      '#description' => $this->t("Please, type your name."),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];

    $form['employee_one_plus'] = [
      '#type' => 'radios',
      '#title' => $this->t('One plus'),
      '#description' => $this->t("Please, check this if you want to bring someone."),
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
      '#default_value' => 0,
      '#required' => TRUE,
    ];

    $form['employee_kids'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount of kids'),
      '#description' => $this->t("How many kids is going."),
      '#default_value' => 0,
      '#min' => 0,
      '#max' => 100,
      '#required' => TRUE,
    ];

    $form['employee_vegetarians'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount of vegetarians'),
      '#description' => $this->t("How many vegetarians is going."),
      '#default_value' => 0,
      '#min' => 0,
      '#max' => 100,
      '#required' => TRUE,
    ];

    $form['employee_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#description' => $this->t("Please, type email address"),
      '#required' => TRUE,
    ];

    $form['employee_department'] = [
      '#type' => 'email',
      '#title' => $this->t('Department'),
      '#description' => $this->t("Please, type email address"),
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Checks if amount of vegetarians is not higher than
    // the total amount of people ( 1 - it's registering employee ).
    $total_people = $values['employee_kids'] + $values['employee_one_plus'] + 1;
    if ($values['employee_vegetarians'] > ($total_people)) {
      $form_state->setErrorByName('employee_vegetarians', $this->t('The number of vegetarians - %vegetarians is higher than number of people - @total.', [
        '%vegetarians' => $values['employee_vegetarians'],
        '@total' => $total_people,
      ]));
    }

    // Checks if employee is not registered yet.
    if ($this->nodeStorage->loadByProperties([
      'type' => 'registration',
      'field_email_address' => $values['employee_email'],
    ])) {
      $form_state->setErrorByName('employee_email', $this->t("Sorry, the email address - %address already registered for annual event.", [
        '%address' => $values['employee_email'],
      ]));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    try {
      $this->nodeStorage->create([
        'type' => 'registration',
        'title' => Xss::filter($values['employee_name']),
        'field_name_of_the_employee' => $values['employee_name'],
        'field_one_plus' => $values['employee_one_plus'],
        'field_amount_of_kids' => $values['employee_kids'],
        'field_amount_of_vegetarians' => $values['employee_vegetarians'],
        'field_email_address' => $values['employee_email'],
      ])->save();

      $this->messenger()
        ->addStatus($this->t("Registered for event successfully!"));
    }
    catch (\Exception $e) {
      $this->messenger()
        ->addError($this->t("Sorry, seems that 'Registration' content type is not created!"));
    }

  }

}
