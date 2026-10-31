import { initTheme } from './modules/theme.js';
import { initPremiumHeader } from './modules/header.js';
import { initHeroSlider } from './modules/hero-slider.js';
import { initLanguageSelector, initMobileMenus } from './modules/language.js';

initTheme();
initLanguageSelector();

initMobileMenus();

initHeroSlider();

initPremiumHeader();
