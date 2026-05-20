<?php

declare(strict_types=1);

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
    <?php panel_field_textarea("Описание", "promo_description[]", $description, 3); ?>
  </div>
  <?php
}
