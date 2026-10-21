(function () {
    'use strict';
    if (window.__CMS_NEWSLETTER_V9_BOOTED__) return;
    window.__CMS_NEWSLETTER_V9_BOOTED__ = true;

    function getBaseUrl() {
        var b = document.body ? document.body.getAttribute('data-base-url') : '';
        if (!b) b = window.location.origin + '/';
        return b.endsWith('/') ? b : b + '/';
    }

    function applyNewsletterColors() {
        document.querySelectorAll('.cms-newsletter-section, .cms-newsletter-box').forEach(function (el) {
            var attrMap = {
                'data-bg-color': '--nl-bg',
                'data-border-color': '--nl-border',
                'data-text-color': '--nl-text',
                'data-btn-bg': '--nl-btn-bg',
                'data-btn-text-col': '--nl-btn-txt',
                'data-card-bg-light': '--cms-card-bg-light',
                'data-text-light': '--cms-text-light'
            };
            Object.keys(attrMap).forEach(function (k) {
                var v = el.getAttribute(k);
                if (v) el.style.setProperty(attrMap[k], v);
            });
        });
    }

    function bindSubscribeForms() {
        var forms = document.querySelectorAll('.js-cms-newsletter-form, .js-cms-lead-form');
        forms.forEach(function (form) {
            if (form.dataset.nlAjaxBound === '1') return;
            form.dataset.nlAjaxBound = '1';

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();

                var btn = form.querySelector('button[type="submit"]');
                var lbl = form.querySelector('.js-nl-btn-label') || btn;
                var feedback = form.querySelector('.js-lead-feedback');
                var origText = lbl ? lbl.textContent : 'Subscribe';

                if (btn) btn.disabled = true;
                if (lbl) lbl.textContent = 'Sending...';
                if (feedback) {
                    feedback.textContent = '';
                    feedback.className = 'js-lead-feedback field-hint cms-nl-feedback';
                }

                var formData = new FormData(form);
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                var actionUrl = form.getAttribute('action') || (getBaseUrl() + 'addons/newsletter/subscribe');

                fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(function (res) {
                    return res.json();
                })
                .then(function (data) {
                    if (data && data.csrf_token) {
                        if (csrfMeta) csrfMeta.setAttribute('content', data.csrf_token);
                        document.querySelectorAll('input[name^="csrf_"]').forEach(function (inp) {
                            inp.value = data.csrf_token;
                        });
                    }
                    var isOk = data && (data.status === 'success' || data.ok === true);
                    if (feedback) {
                        feedback.textContent = (data && data.message) ? data.message : (isOk ? 'Subscribed!' : 'Subscription failed.');
                        feedback.className = 'js-lead-feedback field-hint cms-nl-feedback ' + (isOk ? 'is-ok' : 'is-err');
                    }
                    if (isOk) {
                        form.reset();
                    }
                })
                .catch(function () {
                    if (feedback) {
                        feedback.textContent = 'Unable to submit right now. Please try again.';
                        feedback.className = 'js-lead-feedback field-hint cms-nl-feedback is-err';
                    }
                })
                .finally(function () {
                    if (btn) btn.disabled = false;
                    if (lbl) lbl.textContent = origText;
                });
            });
        });
    }

    function bindAdminCrmHandlers() {
        document.querySelectorAll('.js-edit-nl-lead').forEach(function (btn) {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function () {
                var idEl = document.getElementById('nl_id');
                if (!idEl) return;
                idEl.value = btn.getAttribute('data-id') || '0';
                document.getElementById('nl_email').value = btn.getAttribute('data-email') || '';
                document.getElementById('nl_name').value = btn.getAttribute('data-name') || '';
                document.getElementById('nl_phone').value = btn.getAttribute('data-phone') || '';
                var topicEl = document.getElementById('nl_topic');
                if (topicEl) topicEl.value = btn.getAttribute('data-topic') || '';
                document.getElementById('nl_tag').value = btn.getAttribute('data-tag') || 'Website Lead Magnet';
                document.getElementById('nl_status').value = btn.getAttribute('data-status') || 'subscribed';
                var h = document.getElementById('nl-form-heading');
                if (h) h.textContent = 'Edit Subscriber #' + idEl.value;
            });
        });

        var resetBtn = document.getElementById('js-nl-reset-btn');
        if (resetBtn && !resetBtn.dataset.bound) {
            resetBtn.dataset.bound = '1';
            resetBtn.addEventListener('click', function () {
                document.getElementById('nl_id').value = '0';
                document.getElementById('nl_email').value = '';
                document.getElementById('nl_name').value = '';
                document.getElementById('nl_phone').value = '';
                var topicEl = document.getElementById('nl_topic');
                if (topicEl) topicEl.value = '';
                document.getElementById('nl_tag').value = 'Website Lead Magnet';
                document.getElementById('nl_status').value = 'subscribed';
                var h = document.getElementById('nl-form-heading');
                if (h) h.textContent = 'Add / Edit Subscriber';
            });
        }

        document.querySelectorAll('.js-nl-auto-submit').forEach(function (sel) {
            if (sel.dataset.bound === '1') return;
            sel.dataset.bound = '1';
            sel.addEventListener('change', function () {
                if (sel.form) sel.form.submit();
            });
        });

        document.querySelectorAll('.js-nl-delete-form').forEach(function (frm) {
            if (frm.dataset.bound === '1') return;
            frm.dataset.bound = '1';
            frm.addEventListener('submit', function (e) {
                if (!window.confirm('Delete this subscriber permanently?')) {
                    e.preventDefault();
                }
            });
        });
    }

    function initAll() {
        applyNewsletterColors();
        bindSubscribeForms();
        bindAdminCrmHandlers();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
