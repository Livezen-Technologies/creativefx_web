<?php // Dark-mode toggle switch. SSR-safe: thumb position + icon are CSS-driven off
      // the `html.dark` class (set pre-paint), Alpine only handles the click. ?>
<button type="button" x-data="themeToggle()" role="switch"
        :aria-checked="dark ? 'true' : 'false'" @click="toggle()"
        class="theme-switch" aria-label="<?= esc(lang('Site.experience.theme') ?: 'Toggle dark mode', 'attr') ?>">
    <span class="theme-switch__track">
        <span class="theme-switch__thumb">
            <!-- Sun (shown in light mode) -->
            <svg class="theme-switch__sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
            </svg>
            <!-- Moon (shown in dark mode) -->
            <svg class="theme-switch__moon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>
            </svg>
        </span>
    </span>
</button>
