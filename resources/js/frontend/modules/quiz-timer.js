// Browser timer is informational. QuizAttemptController enforces the deadline.
export function initQuizTimer() {
    const display = document.querySelector('[data-quiz-deadline]');
    if (!display) return;
    const target = Date.parse(display.dataset.quizDeadline || '');
    const count = display.querySelector('[data-quiz-remaining]');
    const form = document.querySelector('[data-quiz-form]');
    if (!Number.isFinite(target) || !count) return;

    const update = () => {
        const seconds = Math.max(0, Math.ceil((target - Date.now()) / 1000));
        const minutesPart = String(Math.floor(seconds / 60)).padStart(2, '0');
        const secondsPart = String(seconds % 60).padStart(2, '0');
        count.textContent = minutesPart + ':' + secondsPart;
        if (seconds === 0 && form) {
            form.querySelectorAll('button[type=submit]').forEach(button => {
                button.disabled = true;
            });
            display.setAttribute('aria-label', 'Assessment time has expired');
            window.clearInterval(interval);
        }
    };
    const interval = window.setInterval(update, 1000);
    update();
}
