<?php

namespace Drupal\csv_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'csvdisplay_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "csvdisplay_field_formatter",
 *   label = @Translation("CSV Display field formatter"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class CSVDisplayFieldFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'csv_column' => 'first_name', // Column name that you want to display.
        'show_file' => 0,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['csv_column'] = [
      '#title' => $this->t('Column name from CSV file.'),
      '#description' => $this->t('The column name that you want to render from uploaded CSV file. It is expected that all uploaded CSV files should be of same format.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('csv_column'),
    ];

    $form['show_file'] = [
      '#title' => $this->t('Display CSV file.'),
      '#description' => $this->t('Check this checkbox to display CSV file in generic file formatter.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('show_file'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();
    $summary[] = $this->t('Column to display: ') . $settings['csv_column'];
    $summary[] = ($settings['show_file'] == 1) ? $this->t('Display file : Yes') : $this->t('Display file : No');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $item = $file->_referringItem;
      $file_path = file_create_url($file->getFileUri());
      $values_array = array_map('str_getcsv', file($file_path));
      $header = array_shift($values_array);
      $column_index = array_search($this->getSetting('csv_column'), $header);
      foreach ($values_array as $key => $value) {
        $csv_values[] = $value[$column_index];
      }
      $item_list = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#wrapper_attributes' => [
          'class' => [
            'wrapper',
          ],
        ],
        '#attributes' => [
          'class' => [
            'wrapper__links',
          ],
        ],
        '#items' => $csv_values,
      ];

      $elements[$delta] = $item_list;
      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $elements[$delta] += ['#attributes' => []];
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    // If show file checkbox is checked then display file in generic file
    // formatter.
    $index = sizeof($elements);
    if ($this->getSetting('show_file')) {
      foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
        $item = $file->_referringItem;
        $elements[$index] = [
          '#theme' => 'file_link',
          '#file' => $file,
          '#description' => $item->description,
          '#cache' => [
            'tags' => $file->getCacheTags(),
          ],
        ];
        // Pass field item attributes to the theme function.
        if (isset($item->_attributes)) {
          $elements[$index] += ['#attributes' => []];
          $elements[$index]['#attributes'] += $item->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and should not be rendered in the field template.
          unset($item->_attributes);
        }
        $index++;
      }
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
