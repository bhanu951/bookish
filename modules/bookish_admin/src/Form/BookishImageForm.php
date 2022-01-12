<?php

namespace Drupal\bookish_admin\Form;

use Drupal\bookish_admin\Ajax\BookishImageCKEditorCommand;
use Drupal\bookish_admin\BookishImageFormTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\image\ImageStyleInterface;

class BookishImageForm extends FormBase {

  use BookishImageFormTrait;

  public function getFormId() {
    return 'bookish_admin_image_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, FileInterface $file = NULL, ImageStyleInterface $image_style = NULL) {
    if (!$file || !$image_style) {
      \Drupal::messenger()->addError('You cannot access this form.');
      return $form;
    }

    $image_data = _bookish_admin_coerce_data(json_decode($file->bookish_image_data->getString(), TRUE));

    $unique_id = $file->id() . '-' . $image_style->getName();
    $preview_id = 'bookish-image-preview-' . $unique_id;

    $image_styles = ImageStyle::loadMultiple();
    $bookish_styles = [];
    $other_styles = [];
    foreach ($image_styles as $name => $style) {
      $is_bookish = FALSE;
      foreach ($style->getEffects() as $effect) {
        if (strpos(get_class($effect), 'bookish_admin') !== FALSe) {
          $is_bookish = TRUE;
          break;
        }
      }
      if ($is_bookish) {
        $bookish_styles[$name] = $style->label();
      } else {
        $other_styles[$name] = $style->label();
      }
    }

    $options = [
      'none' => 'None',
      'Bookish image styles' => $bookish_styles,
      'Other image styles' => $other_styles,
    ];

    $form['preview_wrapper'] = [
      '#type' => 'container',
      'preview' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['bookish-image-preview'],
        ],
        '#id' => $preview_id,
        'image' => static::getPreviewElement($file, $image_style, $image_data),  
        'image_style' => [
          '#title' => t('Image Style'),
          '#type' => 'select',
          '#options' => $options,    
          '#default_value' => 'original',
          '#ajax' => static::getAjaxSettings($form, $preview_id),
        ],
      ],
    ];

    $form['#file'] = $file;
    $form['#image_style'] = $image_style;

    $form = $this->buildImageForm($form, $unique_id, $file);

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => [
        'class' => [
          'bookish-image-form-actions'
        ],
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Update'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => [$this, 'submitAjax'],
        'event' => 'click',
      ],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => t('Cancel'),
      '#cancel' => TRUE,
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [$this, 'cancelAjax'],
        'event' => 'click',
      ],
    ];

    return $form;
  }

  public static function updatePreview(&$form, FormStateInterface &$form_state) {
    $file = $form['#file'];
    $image_data = json_decode($file->bookish_image_data->getString(), TRUE);
    $new_image_data = $form_state->getValue(['bookish_image', 'bookish_image_data']);
    $image_data = array_merge(_bookish_admin_coerce_data($image_data), _bookish_admin_coerce_data($new_image_data));

    $form['preview_wrapper']['preview']['image'] = static::getPreviewElement($file, $form['#image_style'], $image_data);

    return $form['preview_wrapper']['preview'];
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  public static function submitAjax(array &$form, FormStateInterface $form_state) {
    /** @var FileInterface $file */
    $file = $form['#file'];
    /** @var ImageStyleInterface $image_style  */
    $image_style = $form['#image_style'];
    $url = $image_style->buildUrl($file->getFileUri());
    $response = new AjaxResponse();
    $response->addCommand(new BookishImageCKEditorCommand($file->uuid(), $url));
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  public static function cancelAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    if (isset($button['#cancel'])) {
      return;
    }
    $file = $form['#file'];
    $image_data = json_decode($file->bookish_image_data->getString(), TRUE);
    $new_image_data = $form_state->getValue(['bookish_image', 'bookish_image_data']);
    $image_data = array_merge(_bookish_admin_coerce_data($image_data), _bookish_admin_coerce_data($new_image_data));
    $file->bookish_image_data = json_encode($image_data);
    _bookish_admin_flush_image_styles($file->getFileUri());  
    $file->save();
  }

}
