<?php

declare(strict_types=1);

require_once __DIR__ . "/rich-text.php";

function panel_field_text(string $label, string $name, string $value = "", string $type = "text", string $hint = ""): void {
  ?>
  <div class="pk-field">
    <label class="pk-label" for="<?php echo html($name); ?>"><?php echo html($label); ?></label>
    <input class="pk-input" id="<?php echo html($name); ?>" name="<?php echo html($name); ?>" type="<?php echo html($type); ?>" value="<?php echo html($value); ?>" />
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

function panel_field_rich_text(string $label, string $name, string $value = "", int $rows = 4, string $hint = ""): void {
  $safe = panel_sanitize_rich_html($value);
  $fieldId = "pk-rich-" . preg_replace("/[^a-z0-9_-]+/i", "-", $name) . "-" . bin2hex(random_bytes(3));
  ?>
  <div class="pk-field pk-rich" data-pk-rich>
    <span class="pk-label"><?php echo html($label); ?></span>
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

function panel_field_media(string $label, string $name, string $value, string $uploadPrefix, bool $portrait = true): void {
  $previewClass = $portrait ? "pk-media__preview" : "pk-media__preview pk-media__preview--wide";
  $hidden = $value === "";
  ?>
  <div class="pk-field">
    <span class="pk-label"><?php echo html($label); ?></span>
    <div class="pk-media" data-pk-media>
      <img class="<?php echo $previewClass; ?>" data-pk-media-preview src="<?php echo html($value); ?>" alt="" <?php echo $hidden ? "hidden" : ""; ?> />
      <input type="hidden" name="<?php echo html($name); ?>" data-pk-media-path value="<?php echo html($value); ?>" />
      <div class="pk-media__row">
        <label class="pk-btn pk-btn--ghost pk-btn--sm pk-file">
          Качи снимка
          <input type="file" accept="image/*" data-pk-upload="<?php echo html($uploadPrefix); ?>" hidden />
        </label>
        <span class="pk-media__path" data-pk-upload-status><?php echo $value ? html($value) : "Няма избрана снимка"; ?></span>
      </div>
    </div>
  </div>
  <?php
}

function panel_promotion_repeater_item(int $index, array $promo = []): void {
  $image = (string)($promo["image"] ?? "");
  $alt = (string)($promo["alt"] ?? "");
  $description = (string)($promo["description"] ?? "");
  ?>
  <div class="pk-repeater-item" data-pk-repeater-item>
    <div class="pk-repeater-item__head">
      <strong>Промоция <?php echo (int)$index + 1; ?></strong>
      <button type="button" class="pk-btn pk-btn--ghost pk-btn--sm pk-btn--danger" data-pk-remove-repeater>Изтрий</button>
    </div>
    <?php panel_field_media("Снимка", "promo_image[]", $image, "promo", true); ?>
    <?php panel_field_text("Alt текст", "promo_alt[]", $alt); ?>
    <?php panel_field_rich_text("Описание", "promo_description[]", $description, 3); ?>
  </div>
  <?php
}
