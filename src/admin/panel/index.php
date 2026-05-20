<?php

declare(strict_types=1);

require_once __DIR__ . "/_inc/util.php";
require_once __DIR__ . "/_inc/ui.php";
require_once __DIR__ . "/_inc/panel-data.php";

require_login();

panel_page_open("Админ панел");
?>
    <div class="pk-wrap">
      <div class="pk-top">
        <div>
          <div class="pk-eyebrow">Ритеил парк Раковски</div>
          <h1 class="pk-title">Админ панел</h1>
          <p class="pk-sub">
            <?php if (panel_is_local_dev()): ?>
              Локален режим — запис в <code>src/_data/</code> и <code>src/assets/images/uploads/</code>
            <?php else: ?>
              Промените се изпращат към GitHub и се публикуват автоматично
            <?php endif; ?>
          </p>
        </div>
        <div class="pk-top__actions">
          <a class="pk-btn pk-btn--ghost" href="./logout.php">Изход</a>
        </div>
      </div>

      <div class="pk-grid">
        <a class="pk-tile" href="./shops.php">
          <div class="pk-tile__title">Обекти и промоции</div>
          <div class="pk-tile__meta">Партньори, снимки, оферти</div>
        </a>
        <a class="pk-tile" href="./news.php">
          <div class="pk-tile__title">Новини</div>
          <div class="pk-tile__meta">Статии и събития</div>
        </a>
        <a class="pk-tile" href="./services.php">
          <div class="pk-tile__title">Услуги</div>
          <div class="pk-tile__meta">Текстови страници</div>
        </a>
        <a class="pk-tile" href="./site.php">
          <div class="pk-tile__title">Настройки</div>
          <div class="pk-tile__meta">Марка, меню, футър, SEO</div>
        </a>
      </div>
    </div>
<?php
panel_page_close();
