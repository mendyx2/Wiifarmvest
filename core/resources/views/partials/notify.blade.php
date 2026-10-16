<div id="notify-container"></div>

<style>
    #notify-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(10, 10, 30, 0.45);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        z-index: 99998;
        animation: nb-in 0.25s ease forwards;
    }

    @keyframes nb-in {
        from { opacity: 0; }
        to   { opacity: 1; }
    }

    #notify-container {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99999;
        pointer-events: none;
        padding: 16px;
    }

    .notify-popup {
        pointer-events: all;
        background: #ffffff;
        border-radius: 24px;
        padding: 36px 32px 28px;
        width: 90%;
        max-width: 400px;
        box-shadow: 0 32px 80px rgba(0,0,0,0.22), 0 8px 24px rgba(0,0,0,0.12);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        overflow: hidden;
        animation: np-in 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    .notify-popup.notify-leaving {
        animation: np-out 0.28s ease-in forwards;
    }

    @keyframes np-in {
        0%   { opacity: 0; transform: scale(0.65) translateY(30px); }
        100% { opacity: 1; transform: scale(1)    translateY(0);    }
    }

    @keyframes np-out {
        0%   { opacity: 1; transform: scale(1)    translateY(0);    }
        100% { opacity: 0; transform: scale(0.8)  translateY(-16px); }
    }

    /* top accent bar */
    .notify-popup::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 5px;
        border-radius: 24px 24px 0 0;
    }

    .notify-popup.notify-success::before { background: linear-gradient(90deg, #11998e, #38ef7d); }
    .notify-popup.notify-error::before   { background: linear-gradient(90deg, #c0392b, #ff6b6b); }
    .notify-popup.notify-warning::before { background: linear-gradient(90deg, #f7971e, #ffd200); }
    .notify-popup.notify-info::before    { background: linear-gradient(90deg, #1e9ff2, #6dd5fa); }

    /* icon circle */
    .np-icon {
        width: 76px;
        height: 76px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 30px;
        color: #fff;
        margin-bottom: 18px;
        flex-shrink: 0;
        position: relative;
    }

    .np-icon::after {
        content: '';
        position: absolute;
        inset: -6px;
        border-radius: 50%;
        opacity: 0.18;
    }

    .notify-popup.notify-success .np-icon { background: linear-gradient(135deg, #11998e, #38ef7d); }
    .notify-popup.notify-success .np-icon::after { background: linear-gradient(135deg, #11998e, #38ef7d); }

    .notify-popup.notify-error .np-icon   { background: linear-gradient(135deg, #c0392b, #ff6b6b); }
    .notify-popup.notify-error .np-icon::after { background: linear-gradient(135deg, #c0392b, #ff6b6b); }

    .notify-popup.notify-warning .np-icon { background: linear-gradient(135deg, #f7971e, #ffd200); }
    .notify-popup.notify-warning .np-icon::after { background: linear-gradient(135deg, #f7971e, #ffd200); }

    .notify-popup.notify-info .np-icon    { background: linear-gradient(135deg, #1e9ff2, #6dd5fa); }
    .notify-popup.notify-info .np-icon::after { background: linear-gradient(135deg, #1e9ff2, #6dd5fa); }

    .np-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 8px;
        letter-spacing: 0.2px;
    }

    .np-message {
        font-size: 0.91rem;
        color: #6c757d;
        line-height: 1.6;
        margin-bottom: 24px;
    }

    .np-btn {
        padding: 11px 38px;
        border-radius: 50px;
        border: none;
        font-size: 0.92rem;
        font-weight: 600;
        color: #fff;
        cursor: pointer;
        letter-spacing: 0.5px;
        transition: transform 0.15s, box-shadow 0.15s;
        outline: none;
    }

    .np-btn:hover  { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
    .np-btn:active { transform: translateY(0);    box-shadow: none; }

    .notify-popup.notify-success .np-btn { background: linear-gradient(135deg, #11998e, #38ef7d); }
    .notify-popup.notify-error .np-btn   { background: linear-gradient(135deg, #c0392b, #ff6b6b); }
    .notify-popup.notify-warning .np-btn { background: linear-gradient(135deg, #f7971e, #ffd200); color: #5a3e00; }
    .notify-popup.notify-info .np-btn    { background: linear-gradient(135deg, #1e9ff2, #6dd5fa); }

    /* auto-dismiss progress bar */
    .np-progress {
        position: absolute;
        bottom: 0; left: 0;
        height: 4px;
        border-radius: 0 0 24px 24px;
        animation: np-progress linear forwards;
    }

    .notify-popup.notify-success .np-progress { background: linear-gradient(90deg, #11998e, #38ef7d); }
    .notify-popup.notify-error .np-progress   { background: linear-gradient(90deg, #c0392b, #ff6b6b); }
    .notify-popup.notify-warning .np-progress { background: linear-gradient(90deg, #f7971e, #ffd200); }
    .notify-popup.notify-info .np-progress    { background: linear-gradient(90deg, #1e9ff2, #6dd5fa); }

    @keyframes np-progress {
        from { width: 100%; }
        to   { width: 0%; }
    }

    /* close X */
    .np-close {
        position: absolute;
        top: 14px; right: 16px;
        background: none;
        border: none;
        font-size: 1rem;
        color: #b2bec3;
        cursor: pointer;
        width: 30px; height: 30px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.2s, color 0.2s;
        outline: none;
    }
    .np-close:hover { background: #f1f2f6; color: #2d3436; }
</style>

<script>
"use strict";

const _npIcons = {
    success: 'fas fa-check',
    error:   'fas fa-times',
    warning: 'fas fa-exclamation',
    info:    'fas fa-info',
};

const _npTitles = {
    success: 'Success',
    error:   'Oops!',
    warning: 'Warning',
    info:    'Info',
};

const _NP_DURATION = 5000;
let   _npQueue     = [];
let   _npShowing   = false;

function _npShowNext() {
    if (_npShowing || _npQueue.length === 0) return;
    _npShowing = true;

    const { status, message } = _npQueue.shift();

    // backdrop
    const bd = document.createElement('div');
    bd.id = 'notify-backdrop';
    document.body.appendChild(bd);

    // popup
    const pop = document.createElement('div');
    pop.className = 'notify-popup notify-' + status;
    pop.innerHTML =
        '<button class="np-close" onclick="_npDismiss(this)"><i class="fas fa-times"></i></button>' +
        '<div class="np-icon"><i class="' + (_npIcons[status] || 'fas fa-bell') + '"></i></div>' +
        '<div class="np-title">' + _npTitles[status] + '</div>' +
        '<div class="np-message">' + message + '</div>' +
        '<button class="np-btn" onclick="_npDismiss(this)">OK</button>' +
        '<div class="np-progress" style="animation-duration:' + _NP_DURATION + 'ms"></div>';

    document.getElementById('notify-container').appendChild(pop);

    pop._npTimer = setTimeout(function() { _npDismiss(pop, true); }, _NP_DURATION);
}

function _npDismiss(el, direct) {
    const pop = direct ? el : el.closest('.notify-popup');
    if (!pop || pop._npDismissed) return;
    pop._npDismissed = true;
    clearTimeout(pop._npTimer);

    pop.classList.add('notify-leaving');

    setTimeout(function() {
        pop.remove();
        const bd = document.getElementById('notify-backdrop');
        if (bd) bd.remove();
        _npShowing = false;
        _npShowNext();
    }, 280);
}

function notify(status, message) {
    if (typeof message === 'string') {
        _npQueue.push({ status: status, message: message });
    } else {
        message.forEach(function(m) { _npQueue.push({ status: status, message: m }); });
    }
    _npShowNext();
}

// fire session notifications
const notifications = @json(session('notify', []));
const errors = @json(@$errors ? collect($errors->all())->unique() : []);

if (notifications.length) {
    notifications.forEach(function(el) { notify(el[0], el[1]); });
}
if (errors.length) {
    errors.forEach(function(err) { notify('error', err); });
}
</script>
