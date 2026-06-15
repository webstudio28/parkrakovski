<?php

declare(strict_types=1);

require_once __DIR__ . "/rich-text.php";
require_once __DIR__ . "/panel-data.php";
require_once __DIR__ . "/shop-hours.php";
require_once __DIR__ . "/media.php";

function panel_field_text(string $label, string $name, string $value = "", string $type = "text", string $hint = "", string $extraAttrs = ""): void {
  ?>
  <div class="pk-field">
    <label class="pk-label" for="<?php echo html($name); ?>"><?php echo html($label); ?></label>
    <input class="pk-input" id="<?php echo html($name); ?>" name="<?php echo html($name); ?>" type="<?php echo html($type); ?>" value="<?php echo html($value); ?>" <?php echo $extraAttrs; ?> />
    <?php if ($hint): ?><p class="pk-hint"><?php echo html($hint); ?></p><?php endif; ?>
  </div>
  <?php
}

function panel_field_textarea(string $label, string $name, string $value = "", int $rows = 4): void {
  ?>
  <div class="pk-field">
    <label class="pk-label" for="<?php echo html($name); ?>"><?php echo html($label); ?></label>
    <textarea class="pk-input" id="<?php echo html($name); ?>" name="<?php echo html($name); ?>" rows="<?php echo (int)$rows; ?>" style="min-height:0;font-family:inherit;"><?php echo html($value); ?></textarea>
  </div>
  <?php
}

function panel_field_rich_text(string $label, string $name, string $value = "", int $rows = 4, string $hint = "", int $maxPlainChars = 0): void {
  if ($maxPlainChars > 0) {
    $value = panel_rich_html_truncate($value, $maxPlainChars);
  }
  $safe = panel_sanitize_rich_html($value);
  $fieldId = "pk-rich-" . preg_replace("/[^a-z0-9_-]+/i", "-", $name) . "-" . bin2hex(random_bytes(3));
  ?>
  <div
    class="pk-field pk-rich"
    data-pk-rich
    <?php echo $maxPlainChars > 0 ? 'data-pk-rich-max="' . (int)$maxPlainChars . '"' : ""; ?>
  >
    <div class="pk-rich__label-row">
      <span class="pk-label"><?php echo html($label); ?></span>
      <?php if ($maxPlainChars > 0): ?>
        <span class="pk-rich__count" data-pk-rich-count>0 / <?php echo (int)$maxPlainChars; ?></span>
      <?php endif; ?>
    </div>
    <?php if ($hint): ?><p class="pk-hint" style="margin:0 0 0.5rem;"><?php echo html($hint); ?></p><?php endif; ?>
    <div class="pk-rich__toolbar" role="toolbar" aria-label="Форматиране на текст">
      <button type="button" class="pk-rich__btn" data-pk-cmd="bold" title="Удебелен" aria-label="Удебелен"><i class="fa-solid fa-bold" aria-hidden="true"></i></button>
      <button type="button" class="pk-rich__btn" data-pk-cmd="italic" title="Курсив" aria-label="Курсив"><i class="fa-solid fa-italic" aria-hidden="true"></i></button>
      <button type="button" class="pk-rich__btn" data-pk-cmd="underline" title="Подчертан" aria-label="Подчертан"><i class="fa-solid fa-underline" aria-hidden="true"></i></button>
      <span class="pk-rich__sep" aria-hidden="true"></span>
      <button type="button" class="pk-rich__btn" data-pk-icon-toggle title="Добави икона" aria-label="Добави икона" aria-expanded="false"><i class="fa-solid fa-icons" aria-hidden="true"></i></button>
    </div>
    <div
      class="pk-rich__editor pk-input"
      contenteditable="true"
      data-pk-rich-editor
      id="<?php echo html($fieldId); ?>-ed"
      role="textbox"
      aria-multiline="true"
    ><?php echo $safe; ?></div>
    <textarea
      class="pk-rich__source"
      name="<?php echo html($name); ?>"
      id="<?php echo html($fieldId); ?>"
      rows="<?php echo (int)$rows; ?>"
      hidden
      data-pk-rich-source
    ><?php echo html($safe); ?></textarea>
    <div class="pk-icon-picker" data-pk-icon-picker hidden>
      <p class="pk-icon-picker__title">Изберете икона</p>
      <div class="pk-icon-picker__grid">
        <?php foreach (panel_rich_icon_catalog() as $icon): ?>
          <button type="button" class="pk-icon-picker__item" data-pk-icon="<?php echo html($icon[0]); ?>" title="<?php echo html($icon[1]); ?>" aria-label="<?php echo html($icon[1]); ?>">
            <i class="<?php echo html($icon[0]); ?>" aria-hidden="true"></i>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php
}

/**
 * Full-featured body editor for news articles (powered by Quill.js).
 * Supports headings, paragraphs, lists, blockquotes, links, and inline images.
 */
function panel_field_body_editor(string $label, string $name, string $value = ""): void {
  $safe = panel_sanitize_body_html($value);
  $fieldId = "pk-body-" . bin2hex(random_bytes(3));
  $modalId = $fieldId . "-img-modal";
  ?>
  <div class="pk-field">
    <div class="pk-rich__label-row" style="margin-bottom:.5rem;">
      <span class="pk-label"><?php echo html($label); ?></span>
      <span class="pk-body-wordcount" data-pk-body-count></span>
    </div>

    <div
      class="pk-body-wrap"
      data-pk-body-wrap
      data-pk-body-source="<?php echo html($fieldId); ?>"
      data-pk-body-modal="<?php echo html($modalId); ?>"
    >
      <div class="pk-body-editor-shell">
        <div id="<?php echo html($fieldId . "-editor"); ?>" data-pk-body-editor-div></div>
      </div>
    </div>

    <textarea
      name="<?php echo html($name); ?>"
      id="<?php echo html($fieldId); ?>"
      hidden
      data-pk-body-source-textarea
    ><?php echo html($safe); ?></textarea>
  </div>

  <!-- Image insert modal -->
  <div
    class="pk-body-img-modal"
    id="<?php echo html($modalId); ?>"
    hidden
    role="dialog"
    aria-modal="true"
    aria-label="Вмъкни снимка в текста"
  >
    <div class="pk-body-img-modal__backdrop" data-pk-body-modal-close></div>
    <div class="pk-body-img-modal__box">
      <div class="pk-body-img-modal__head">
        <span class="pk-body-img-modal__title">Вмъкни снимка</span>
        <button type="button" class="pk-body-img-modal__close" data-pk-body-modal-close aria-label="Затвори">&times;</button>
      </div>
      <div class="pk-body-img-modal__body">
        <button type="button" class="pk-btn pk-btn--ghost pk-btn--sm pk-body-img-modal__pick-btn" data-pk-body-img-btn onclick="this.closest('.pk-body-img-modal').querySelector('[data-pk-body-img-file]').click()">
          <i class="fa-solid fa-image" aria-hidden="true"></i>
          <span data-pk-body-img-btn-label>Избери и качи снимка</span>
        </button>
        <input
          type="file"
          accept="<?php echo html(panel_upload_accept_attr()); ?>"
          data-pk-body-img-file
          hidden
        />
        <img
          data-pk-body-img-preview
          class="pk-body-img-modal__preview"
          alt="Преглед"
          hidden
        />
        <p class="pk-body-img-modal__status" data-pk-body-img-status></p>
        <p class="pk-hint" style="margin:.5rem 0 0;"><?php echo html(panel_upload_rules_hint()); ?></p>
      </div>
    </div>
  </div>
  <?php
}

function panel_field_media(string $label, string $name, string $value, string $uploadPrefix, bool $portrait = true): void {
  $previewClass = $portrait ? "pk-media__preview" : "pk-media__preview pk-media__preview--wide";
  $hidden = $value === "";
  $hasImage = $value !== "";
  $btnLabel = $hasImage ? "Смени снимка" : "Качи снимка";
  ?>
  <div class="pk-field">
    <span class="pk-label"><?php echo html($label); ?></span>
    <div class="pk-media" data-pk-media>
      <img class="<?php echo $previewClass; ?>" data-pk-media-preview src="<?php echo html($value); ?>" alt="" <?php echo $hidden ? "hidden" : ""; ?> />
      <input type="hidden" name="<?php echo html($name); ?>" data-pk-media-path value="<?php echo html($value); ?>" />
      <div class="pk-media__row">
        <label class="pk-btn pk-btn--ghost pk-btn--sm pk-file">
          <span data-pk-upload-btn-text><?php echo html($btnLabel); ?></span>
          <input type="file" accept="<?php echo html(panel_upload_accept_attr()); ?>" data-pk-upload="<?php echo html($uploadPrefix); ?>" hidden />
        </label>
        <span class="pk-media__path" data-pk-upload-status <?php echo $hasImage ? "hidden" : ""; ?>><?php echo $hasImage ? "" : "Няма избрана снимка"; ?></span>
      </div>
      <p class="pk-hint" style="margin:0.35rem 0 0;"><?php echo html(panel_upload_rules_hint()); ?></p>
    </div>
  </div>
  <?php
}

/**
 * @param array<string, array{closed: bool, open: string, close: string}> $schedule
 */
function panel_field_shop_hours(array $schedule): void {
  $labels = panel_shop_day_labels();
  ?>
  <div class="pk-hours" data-pk-hours>
    <p class="pk-hint" style="margin:0 0 1rem;">Задайте начало и край за всеки ден или маркирайте „Затворено“.</p>
    <?php foreach ($labels as $key => $label): ?>
      <?php
      $day = $schedule[$key] ?? ["closed" => false, "open" => "", "close" => ""];
      $closed = !empty($day["closed"]);
      $open = (string)($day["open"] ?? "");
      $close = (string)($day["close"] ?? "");
      ?>
      <div class="pk-hours__row" data-pk-hours-row>
        <span class="pk-hours__day"><?php echo html($label); ?></span>
        <label class="pk-hours__closed">
          <input type="checkbox" name="hours_closed[<?php echo html($key); ?>]" value="1" data-pk-hours-closed <?php echo $closed ? "checked" : ""; ?> />
          Затворено
        </label>
        <div class="pk-hours__times" data-pk-hours-times>
          <input
            class="pk-hours__time"
            type="time"
            name="hours_open[<?php echo html($key); ?>]"
            value="<?php echo html($open); ?>"
            <?php echo $closed ? "disabled" : ""; ?>
          />
          <span class="pk-hours__sep" aria-hidden="true">–</span>
          <input
            class="pk-hours__time"
            type="time"
            name="hours_close[<?php echo html($key); ?>]"
            value="<?php echo html($close); ?>"
            <?php echo $closed ? "disabled" : ""; ?>
          />
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php
}

function panel_gallery_thumb_item(string $path = ""): void {
  ?>
  <div class="pk-gallery__item" data-pk-gallery-item>
    <button type="button" class="pk-gallery__remove" data-pk-gallery-remove aria-label="Премахни снимка">&times;</button>
    <img
      class="pk-gallery__thumb"
      src="<?php echo $path !== "" ? html($path) : ""; ?>"
      alt=""
      loading="lazy"
      decoding="async"
      <?php echo $path === "" ? "hidden" : ""; ?>
    />
    <input type="hidden" name="gallery_image[]" value="<?php echo html($path); ?>" />
  </div>
  <?php
}

/** @param list<string> $paths */
function panel_field_shop_gallery(
  array $paths,
  string $uploadPrefix = "shop-gallery",
  string $title = "Галерия",
  string $hint = "Каруселът на страницата на обекта. Ако няма снимки, секцията не се показва."
): void {
  ?>
  <div class="pk-section">
    <div class="pk-gallery" data-pk-gallery>
      <div class="pk-repeater-item__head">
        <h2 class="pk-section__title" style="margin:0;"><?php echo html($title); ?></h2>
        <button type="button" class="pk-btn pk-btn--ghost pk-btn--sm" data-pk-gallery-add>+ Добави снимки</button>
      </div>
      <?php if ($hint !== ""): ?>
        <p class="pk-hint" style="margin:0 0 1rem;"><?php echo html($hint); ?></p>
      <?php endif; ?>
      <input
        type="file"
        accept="<?php echo html(panel_upload_accept_attr()); ?>"
        multiple
        hidden
        data-pk-gallery-input
        data-pk-upload-prefix="<?php echo html($uploadPrefix); ?>"
      />
      <div class="pk-gallery__grid" data-pk-gallery-grid>
        <?php foreach ($paths as $path): ?>
          <?php panel_gallery_thumb_item($path); ?>
        <?php endforeach; ?>
      </div>
      <p class="pk-gallery__empty" data-pk-gallery-empty <?php echo $paths === [] ? "" : "hidden"; ?>>Все още няма снимки в галерията.</p>
      <p class="pk-err" data-pk-gallery-error hidden role="alert"></p>
      <p class="pk-hint" style="margin:0.75rem 0 0;"><?php echo html(panel_upload_rules_hint()); ?></p>
      <template data-pk-gallery-item-template>
        <?php panel_gallery_thumb_item(""); ?>
      </template>
    </div>
  </div>
  <?php
}

function panel_field_promo_media(string $name, string $value, string $uploadPrefix): void {
  $hidden = $value === "";
  $hasImage = $value !== "";
  $btnLabel = $hasImage ? "Смени снимка" : "Качи снимка";
  ?>
  <div class="pk-promo-media" data-pk-media>
    <img
      class="pk-promo-media__thumb"
      data-pk-media-preview
      src="<?php echo $hasImage ? html($value) : ""; ?>"
      alt=""
      loading="lazy"
      decoding="async"
      <?php echo $hidden ? "hidden" : ""; ?>
    />
    <input type="hidden" name="<?php echo html($name); ?>" data-pk-media-path value="<?php echo html($value); ?>" />
    <label class="pk-btn pk-btn--ghost pk-btn--sm pk-file">
      <span data-pk-upload-btn-text><?php echo html($btnLabel); ?></span>
      <input type="file" accept="<?php echo html(panel_upload_accept_attr()); ?>" data-pk-upload="<?php echo html($uploadPrefix); ?>" hidden />
    </label>
    <span class="pk-media__path" data-pk-upload-status <?php echo $hasImage ? "hidden" : ""; ?>><?php echo $hasImage ? "" : "Няма снимка"; ?></span>
    <p class="pk-hint" style="margin:0.35rem 0 0;"><?php echo html(panel_upload_rules_hint()); ?></p>
  </div>
  <?php
}

function panel_promotion_repeater_item(int $index, array $promo = []): void {
  $image = (string)($promo["image"] ?? "");
  $description = (string)($promo["description"] ?? "");
  ?>
  <div class="pk-promo-card pk-repeater-item" data-pk-repeater-item>
    <div class="pk-repeater-item__head">
      <strong>Промоция <?php echo (int)$index + 1; ?></strong>
      <button type="button" class="pk-btn pk-btn--ghost pk-btn--sm pk-btn--danger" data-pk-remove-repeater>Изтрий</button>
    </div>
    <div class="pk-promo-card__body">
      <?php panel_field_promo_media("promo_image[]", $image, "promo"); ?>
      <div class="pk-promo-card__fields">
        <?php panel_field_rich_text(
          "Описание",
          "promo_description[]",
          $description,
          3,
          "Макс. " . panel_shop_promo_description_max() . " знака.",
          panel_shop_promo_description_max()
        ); ?>
      </div>
    </div>
  </div>
  <?php
}
