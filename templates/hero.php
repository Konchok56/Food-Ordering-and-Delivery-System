<section class="hero" id="home">
  <div class="hero-content">
    <div class="hero-badge">
      <span class="dot"></span>
      <?= __('hero.badge') ?>
    </div>

    <h1><?= __('hero.headline', [], true) ?></h1>

    <p><?= __('hero.subtext') ?></p>

    <div class="hero-actions">
      <a class="btn-lg btn-orange" href="#menu"><?= __('hero.cta_order') ?></a>
      <a class="btn-lg btn-outline-dark" href="#how-it-works">
        <span class="play-icon">
          <svg width="10" height="12" viewBox="0 0 10 12" aria-hidden="true">
            <polygon points="0,0 10,6 0,12"></polygon>
          </svg>
        </span>
        <?= __('hero.cta_how') ?>
      </a>
    </div>

    <div class="hero-stats">
      <div class="stat">
        <div class="stat-num">500<span>+</span></div>
        <div class="stat-label"><?= __('hero.stat_restaurants') ?></div>
      </div>
      <div class="stat">
        <div class="stat-num">30<span>min</span></div>
        <div class="stat-label"><?= __('hero.stat_delivery') ?></div>
      </div>
      <div class="stat">
        <div class="stat-num">98<span>%</span></div>
        <div class="stat-label"><?= __('hero.stat_customers') ?></div>
      </div>
    </div>
  </div>

  <div class="hero-visual">
    <div class="food-circle">
      <div class="food-circle-inner">🍔</div>

      <div class="float-card" style="top: 30px; right: -50px;">
        <div class="fc-icon orange">⚡</div>
        <div>
          <div class="fc-label"><?= __('hero.float_time_label') ?></div>
          <div class="fc-value"><?= __('hero.float_time_val') ?></div>
        </div>
      </div>

      <div class="float-card" style="bottom: 60px; left: -70px; animation-delay: .8s;">
        <div class="fc-icon green">🌟</div>
        <div>
          <div class="fc-label"><?= __('hero.float_rating_label') ?></div>
          <div class="fc-value"><?= __('hero.float_rating_val') ?></div>
        </div>
      </div>

      <div class="float-card" style="top: 200px; left: -90px; animation-delay: 1.4s;">
        <div class="fc-icon yellow">🎉</div>
        <div>
          <div class="fc-label"><?= __('hero.float_orders_label') ?></div>
          <div class="fc-value"><?= __('hero.float_orders_val') ?></div>
        </div>
      </div>
    </div>
  </div>
</section>


  <div class="hero-visual">
    <div class="food-circle">
      <div class="food-circle-inner">🍔</div>

      <div class="float-card" style="top: 30px; right: -50px;">
        <div class="fc-icon orange">⚡</div>
        <div>
          <div class="fc-label">Delivery Time</div>
          <div class="fc-value">28 mins</div>
        </div>
      </div>

      <div class="float-card" style="bottom: 60px; left: -70px; animation-delay: .8s;">
        <div class="fc-icon green">🌟</div>
        <div>
          <div class="fc-label">Customer Rating</div>
          <div class="fc-value">4.9 / 5.0</div>
        </div>
      </div>

      <div class="float-card" style="top: 200px; left: -90px; animation-delay: 1.4s;">
        <div class="fc-icon yellow">🎉</div>
        <div>
          <div class="fc-label">Orders Today</div>
          <div class="fc-value">2,410</div>
        </div>
      </div>
    </div>
  </div>
</section>
