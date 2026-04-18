import './bootstrap';

const formatters = {
    date: new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }),
    time: new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    }),
};

const updateTopbarClock = () => {
    const dateElement = document.querySelector('[data-current-date]');
    const timeElement = document.querySelector('[data-current-time]');

    if (!dateElement || !timeElement) {
        return;
    }

    const now = new Date();
    dateElement.textContent = formatters.date.format(now);
    timeElement.textContent = formatters.time.format(now);
};

updateTopbarClock();
window.setInterval(updateTopbarClock, 1000 * 30);
