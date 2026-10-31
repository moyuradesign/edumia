import { initConfirmations } from './modules/confirmations.js';
import { initAdminShell } from './modules/dashboard.js';
import { initAuthPasswordVisibility } from './modules/auth.js';

initConfirmations();
initAuthPasswordVisibility();

initAdminShell();
