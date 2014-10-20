<?php

/**
 * @file
 * Contains \Drupal\comment\CommentLinkBuilder.
 */

namespace Drupal\comment;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Defines a class for building markup for comment links on a commented entity.
 *
 * Comment links include 'login to post new comment', 'add new comment' etc.
 */
class CommentLinkBuilder implements CommentLinkBuilderInterface {

  use StringTranslationTrait;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Comment manager service.
   *
   * @var \Drupal\comment\CommentManagerInterface
   */
  protected $commentManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new CommentLinkBuilder object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\comment\CommentManagerInterface $comment_manager
   *   Comment manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   String translation service.
   */
  public function __construct(AccountInterface $current_user, CommentManagerInterface $comment_manager, ModuleHandlerInterface $module_handler, TranslationInterface $string_translation) {
    $this->currentUser = $current_user;
    $this->commentManager = $comment_manager;
    $this->moduleHandler = $module_handler;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function buildCommentedEntityLinks(ContentEntityInterface $entity, array &$context) {
    $entity_links = array();
    $view_mode = $context['view_mode'];
    if ($view_mode == 'search_index' || $view_mode == 'search_result' || $view_mode == 'print') {
      // Do not add any links if the entity is displayed for:
      // - search indexing.
      // - constructing a search result excerpt.
      // - print.
      return array();
    }

    $fields = $this->commentManager->getFields($entity->getEntityTypeId());
    foreach ($fields as $field_name => $detail) {
      // Skip fields that the entity does not have.
      if (!$entity->hasField($field_name)) {
        continue;
      }
      $links = array();
      $commenting_status = $entity->get($field_name)->status;
      if ($commenting_status != CommentItemInterface::HIDDEN) {
        // Entity has commenting status open or closed.
        $field_definition = $entity->getFieldDefinition($field_name);
        if ($view_mode == 'rss') {
          // Add a comments RSS element which is a URL to the comments of this
          // entity.
          $options = array(
            'fragment' => 'comments',
            'absolute' => TRUE,
          );
          $entity->rss_elements[] = array(
            'key' => 'comments',
            'value' => $entity->url('canonical', $options),
          );
        }
        elseif ($view_mode == 'teaser') {
          // Teaser view: display the number of comments that have been posted,
          // or a link to add new comments if the user has permission, the
          // entity is open to new comments, and there currently are none.
          if ($this->currentUser->hasPermission('access comments')) {
            if (!empty($entity->get($field_name)->comment_count)) {
              $links['comment-comments'] = array(
                'title' => $this->formatPlural($entity->get($field_name)->comment_count, '1 comment', '@count comments'),
                'attributes' => array('title' => $this->t('Jump to the first comment of this posting.')),
                'fragment' => 'comments',
              ) + $entity->urlInfo()->toArray();
              if ($this->moduleHandler->moduleExists('history')) {
                $links['comment-new-comments'] = array(
                  'title' => '',
                  'href' => '',
                  'attributes' => array(
                    'class' => 'hidden',
                    'title' => $this->t('Jump to the first new comment of this posting.'),
                    'data-history-node-last-comment-timestamp' => $entity->get($field_name)->last_comment_timestamp,
                    'data-history-node-field-name' => $field_name,
                  ),
                );
              }
            }
          }
          // Provide a link to new comment form.
          if ($commenting_status == CommentItemInterface::OPEN) {
            $comment_form_location = $field_definition->getSetting('form_location');
            if ($this->currentUser->hasPermission('post comments')) {
              $links['comment-add'] = array(
                'title' => $this->t('Add new comment'),
                'language' => $entity->language(),
                'attributes' => array('title' => $this->t('Add a new comment to this page.')),
                'fragment' => 'comment-form',
              );
              if ($comment_form_location == CommentItemInterface::FORM_SEPARATE_PAGE) {
                $links['comment-add']['route_name'] = 'comment.reply';
                $links['comment-add']['route_parameters'] = array(
                  'entity_type' => $entity->getEntityTypeId(),
                  'entity' => $entity->id(),
                  'field_name' => $field_name,
                );
              }
              else {
                $links['comment-add'] += $entity->urlInfo()->toArray();
              }
            }
            elseif ($this->currentUser->isAnonymous()) {
              $links['comment-forbidden'] = array(
                'title' => $this->commentManager->forbiddenMessage($entity, $field_name),
                'html' => TRUE,
              );
            }
          }
        }
        else {
          // Entity in other view modes: add a "post comment" link if the user
          // is allowed to post comments and if this entity is allowing new
          // comments.
          if ($commenting_status == CommentItemInterface::OPEN) {
            $comment_form_location = $field_definition->getSetting('form_location');
            if ($this->currentUser->hasPermission('post comments')) {
              // Show the "post comment" link if the form is on another page, or
              // if there are existing comments that the link will skip past.
              if ($comment_form_location == CommentItemInterface::FORM_SEPARATE_PAGE || (!empty($entity->get($field_name)->comment_count) && $this->currentUser->hasPermission('access comments'))) {
                $links['comment-add'] = array(
                  'title' => $this->t('Add new comment'),
                  'attributes' => array('title' => $this->t('Share your thoughts and opinions related to this posting.')),
                  'fragment' => 'comment-form',
                );
                if ($comment_form_location == CommentItemInterface::FORM_SEPARATE_PAGE) {
                  $links['comment-add']['route_name'] = 'comment.reply';
                  $links['comment-add']['route_parameters'] = array(
                    'entity_type' => $entity->getEntityTypeId(),
                    'entity' => $entity->id(),
                    'field_name' => $field_name,
                  );
                }
                else {
                  $links['comment-add'] += $entity->urlInfo()->toArray();
                }
              }
            }
            elseif ($this->currentUser->isAnonymous()) {
              $links['comment-forbidden'] = array(
                'title' => $this->commentManager->forbiddenMessage($entity, $field_name),
                'html' => TRUE,
              );
            }
          }
        }
      }

      if (!empty($links)) {
        $entity_links['comment__' . $field_name] = array(
          '#theme' => 'links__entity__comment__' . $field_name,
          '#links' => $links,
          '#attributes' => array('class' => array('links', 'inline')),
        );
        if ($view_mode == 'teaser' && $this->moduleHandler->moduleExists('history') && $this->currentUser->isAuthenticated()) {
          $entity_links['comment__' . $field_name]['#attached']['library'][] = 'comment/drupal.node-new-comments-link';

          // Embed the metadata for the "X new comments" link (if any) on this
          // entity.
          $entity_links['comment__' . $field_name]['#post_render_cache']['history_attach_timestamp'] = array(
            array('node_id' => $entity->id()),
          );
          $entity_links['comment__' . $field_name]['#post_render_cache']['Drupal\comment\CommentViewBuilder::attachNewCommentsLinkMetadata'] = array(
            array(
              'entity_type' => $entity->getEntityTypeId(),
              'entity_id' => $entity->id(),
              'field_name' => $field_name,
            ),
          );
        }
      }
    }
    return $entity_links;
  }

}
