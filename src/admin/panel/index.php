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
          <div class="pk-eyebrow">Ритейл парк Раковски</div>
          <h1 class="pk-title">Админ панел</h1>
          <p class="pk-sub">
            <?php if (panel_is_local_dev()): ?>
              Режим за разработка — промените се виждат след презареждане на сайта
            <?php else: ?>
              Промените се публикуват автоматично до няколко минути след запазяване
            <?php endif; ?>
          </p>
        </div>
        <div class="pk-top__actions">
          <a class="pk-btn pk-btn--ghost" href="./logout.php">Изход</a>
        </div>
      </div>

      <div class="pk-grid">
        <a class="pk-tile" href="./home.php">
          <div class="pk-tile__title">Начална страница</div>
          <div class="pk-tile__meta">Броячи, галерия</div>
        </a>
        <a class="pk-tile" href="./shops.php">
          <div class="pk-tile__title">Обекти и промоции</div>
          <div class="pk-tile__meta">Партньори, снимки, оферти</div>
        </a>
        <a class="pk-tile" href="./news.php">
          <div class="pk-tile__title">Новини</div>
          <div class="pk-tile__meta">Статии и събития</div>
        </a>
        <a class="pk-tile" href="./site.php">
          <div class="pk-tile__title">Контакти</div>
          <div class="pk-tile__meta">Адрес, телефон, работно време, социални мрежи</div>
        </a>
      </div>
    </div>
<?php
panel_page_close();
