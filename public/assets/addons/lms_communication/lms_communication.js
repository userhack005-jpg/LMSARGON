(function () {
    'use strict';
    if (window.__CMS_LMS_COMMUNICATION_V81__) return;
    window.__CMS_LMS_COMMUNICATION_V81__ = true;

    function getBaseUrl() {
        var b = document.body ? document.body.getAttribute('data-base-url') : '';
        if (!b) b = window.location.origin + '/';
        return b.endsWith('/') ? b : b + '/';
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function updateCsrfToken(token) {
        if (!token) return;
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);
        document.querySelectorAll('input[name="csrf_test_name"]').forEach(function (inp) {
            inp.value = token;
        });
    }

    function renderMessagesHtml(messages, viewerRole) {
        if (!Array.isArray(messages) || messages.length === 0) {
            return '<div class="lcm-empty-chat"><p>No messages in this batch conversation yet. Say hello to start!</p></div>';
        }
        return messages.map(function (m) {
            var isMine = (viewerRole === 'teacher' && m.sender_role === 'teacher') ||
                         (viewerRole === 'student' && m.sender_role === 'student');
            return '<div class="lcm-msg-row ' + (isMine ? 'is-mine' : 'is-peer') + '" data-msg-id="' + parseInt(m.id, 10) + '">' +
                '<div class="lcm-msg-bubble">' +
                '  <div class="lcm-msg-sender">' + escapeHtml(m.sender_name) + '</div>' +
                '  <div class="lcm-msg-text">' + escapeHtml(m.message_body) + '</div>' +
                '  <div class="lcm-msg-time">' + escapeHtml(m.time_short) + '</div>' +
                '</div>' +
                '</div>';
        }).join('');
    }

    function initClassroomMessengerPage() {
        var shell     = document.getElementById('js-lcm-messenger-shell');
        var msgBox    = document.getElementById('js-lcm-messages-box');
        var sendForm  = document.getElementById('js-lcm-send-form');
        var msgInp    = document.getElementById('js-lcm-msg-input');
        var searchInp = document.getElementById('js-lcm-contact-search');
        var batchBar  = document.getElementById('js-lcm-batch-filter-bar');
        var childBar  = document.getElementById('js-lcm-child-filter-bar');

        if (msgBox) {
            msgBox.scrollTop = msgBox.scrollHeight;
        }

        if (batchBar) {
            batchBar.addEventListener('click', function (e) {
                var pill = e.target.closest('.js-lcm-batch-pill');
                if (!pill) return;
                batchBar.querySelectorAll('.js-lcm-batch-pill').forEach(function (b) { b.classList.remove('is-active'); });
                pill.classList.add('is-active');
                var bKey = pill.getAttribute('data-batch-key') || 'all';
                document.querySelectorAll('.js-lcm-contact-row').forEach(function (row) {
                    row.style.display = (bKey === 'all' || row.getAttribute('data-batch-key') === bKey) ? '' : 'none';
                });
            });
        }

        if (childBar) {
            childBar.addEventListener('click', function (e) {
                var pill = e.target.closest('.js-lcm-child-pill');
                if (!pill) return;
                childBar.querySelectorAll('.js-lcm-child-pill').forEach(function (b) { b.classList.remove('is-active'); });
                pill.classList.add('is-active');
                var cid = pill.getAttribute('data-child-id') || 'all';
                document.querySelectorAll('.js-lcm-contact-row').forEach(function (row) {
                    row.style.display = (cid === 'all' || row.getAttribute('data-child-id') === cid) ? '' : 'none';
                });
            });
        }

        if (searchInp) {
            searchInp.addEventListener('input', function () {
                var q = searchInp.value.toLowerCase().trim();
                document.querySelectorAll('.js-lcm-contact-row').forEach(function (row) {
                    var txt = row.getAttribute('data-search') || '';
                    row.style.display = (q === '' || txt.indexOf(q) !== -1) ? '' : 'none';
                });
            });
        }

        if (!shell || !msgBox) return;

        var pollUrl    = shell.getAttribute('data-poll-url');
        var studentId  = shell.getAttribute('data-student-id');
        var teacherId  = shell.getAttribute('data-teacher-id');
        var nodeId     = shell.getAttribute('data-node-id');
        var viewerRole = shell.getAttribute('data-viewer-role') || 'student';
        var lastCount  = msgBox.querySelectorAll('.lcm-msg-row').length;

        if (sendForm && msgInp) {
            sendForm.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!msgInp.value.trim()) return;
                var fd = new FormData(sendForm);
                msgInp.value = '';

                fetch(sendForm.action, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.csrf_token) updateCsrfToken(res.csrf_token);
                    if (res && res.ok && Array.isArray(res.messages)) {
                        msgBox.innerHTML = renderMessagesHtml(res.messages, viewerRole);
                        lastCount = res.messages.length;
                        msgBox.scrollTop = msgBox.scrollHeight;
                    }
                })
                .catch(function () {});
            });
        }

        if (pollUrl && parseInt(studentId, 10) > 0 && parseInt(teacherId, 10) > 0) {
            setInterval(function () {
                if (document.visibilityState && document.visibilityState !== 'visible') return;
                var url = pollUrl + '?student_id=' + encodeURIComponent(studentId) +
                          '&teacher_id=' + encodeURIComponent(teacherId) +
                          '&node_id=' + encodeURIComponent(nodeId);
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res && res.ok && Array.isArray(res.messages) && res.messages.length !== lastCount) {
                            msgBox.innerHTML = renderMessagesHtml(res.messages, viewerRole);
                            lastCount = res.messages.length;
                            msgBox.scrollTop = msgBox.scrollHeight;
                        }
                    })
                    .catch(function () {});
            }, 5500);
        }
    }

    function initFeedbackFormPicker() {
        var picker = document.getElementById('js-fb-pair-picker');
        if (!picker) return;
        picker.addEventListener('change', function () {
            var opt = picker.options[picker.selectedIndex];
            if (!opt) return;
            document.getElementById('fb_stu_id').value     = opt.getAttribute('data-stu') || '0';
            document.getElementById('fb_tchr_id').value    = opt.getAttribute('data-tchr') || '0';
            document.getElementById('fb_node_id').value    = opt.getAttribute('data-node') || '0';
            document.getElementById('fb_cohort_id').value  = opt.getAttribute('data-cohort') || '0';
            document.getElementById('fb_batch_name').value = opt.getAttribute('data-batch') || '';
        });
    }

    function bootStudentOnlyFloatingWidget() {
        if (document.getElementById('js-lcm-messenger-shell')) return;
        if (!document.querySelector('.app-shell')) return;

        fetch(getBaseUrl() + 'chat/widget-context-json', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (ctx) {
            if (!ctx || !ctx.allow_widget || !Array.isArray(ctx.pairs) || ctx.pairs.length === 0) {
                return;
            }

            var activePair = ctx.pairs[0];
            var launcher   = document.createElement('button');
            launcher.type      = 'button';
            launcher.className = 'lcm-float-launcher';
            launcher.innerHTML = '💬 Ask My Teacher (' + escapeHtml(activePair.teacher_name) + ')';

            var drawer = document.createElement('div');
            drawer.className = 'lcm-float-drawer is-hidden';
            drawer.innerHTML = '<div class="lcm-fd-head">' +
                '  <div>' +
                '    <strong style="font-size:0.88rem;">👩‍🏫 ' + escapeHtml(activePair.teacher_name) + '</strong>' +
                '    <div class="mono-cell text-emerald" style="font-size:0.72rem;">🏫 ' + escapeHtml(activePair.batch_title) + '</div>' +
                '  </div>' +
                '  <div style="display:flex;gap:0.35rem;">' +
                '    <a href="' + getBaseUrl() + 'chat/portal" class="btn-action" style="text-decoration:none;font-size:0.72rem;">↗ Full View</a>' +
                '    <button type="button" class="btn-action js-fd-close" style="font-size:0.75rem;">✕</button>' +
                '  </div>' +
                '</div>' +
                '<div class="lcm-fd-body" id="js-lcm-fd-body"><p class="muted-cell" style="text-align:center;margin:auto;">Click to load messages...</p></div>' +
                '<form class="lcm-fd-form" id="js-lcm-fd-form">' +
                '  <input type="text" class="field-input" id="js-lcm-fd-input" placeholder="Ask a question..." autocomplete="off" required style="padding:0.45rem 0.65rem;font-size:0.84rem;">' +
                '  <button type="submit" class="btn-primary btn-sm">➤</button>' +
                '</form>';

            document.body.appendChild(launcher);
            document.body.appendChild(drawer);

            var fdBody    = document.getElementById('js-lcm-fd-body');
            var fdForm    = document.getElementById('js-lcm-fd-form');
            var fdInp     = document.getElementById('js-lcm-fd-input');
            var isOpen    = false;
            var pollTimer = null;

            function loadWidgetMessages() {
                if (!isOpen) return;
                var url = getBaseUrl() + 'chat/poll-messages-json?student_id=' + encodeURIComponent(activePair.student_id) +
                          '&teacher_id=' + encodeURIComponent(activePair.teacher_id) +
                          '&node_id=' + encodeURIComponent(activePair.node_id);
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res && res.ok) {
                            fdBody.innerHTML = renderMessagesHtml(res.messages || [], 'student');
                            fdBody.scrollTop = fdBody.scrollHeight;
                        }
                    })
                    .catch(function () {});
            }

            launcher.addEventListener('click', function () {
                isOpen = !isOpen;
                drawer.classList.toggle('is-hidden', !isOpen);
                if (isOpen) {
                    loadWidgetMessages();
                    fdInp.focus();
                    if (!pollTimer) {
                        pollTimer = setInterval(function () {
                            if (isOpen && (!document.visibilityState || document.visibilityState === 'visible')) {
                                loadWidgetMessages();
                            }
                        }, 6000);
                    }
                } else if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
            });

            drawer.querySelector('.js-fd-close').addEventListener('click', function () {
                isOpen = false;
                drawer.classList.add('is-hidden');
                if (pollTimer) {
                    clearInterval(pollTimer);
                    pollTimer = null;
                }
            });

            fdForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var text = fdInp.value.trim();
                if (!text) return;
                fdInp.value = '';

                var fd = new FormData();
                fd.append('student_user_id', activePair.student_id);
                fd.append('teacher_user_id', activePair.teacher_id);
                fd.append('node_id', activePair.node_id);
                fd.append('cohort_id', activePair.cohort_id);
                fd.append('message_body', text);
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (csrfMeta) fd.append('csrf_test_name', csrfMeta.getAttribute('content'));

                fetch(getBaseUrl() + 'chat/send-message', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd
                })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.csrf_token) updateCsrfToken(res.csrf_token);
                    if (res && res.ok) {
                        fdBody.innerHTML = renderMessagesHtml(res.messages || [], 'student');
                        fdBody.scrollTop = fdBody.scrollHeight;
                    }
                })
                .catch(function () {});
            });
        })
        .catch(function () {});
    }

    function initAll() {
        initClassroomMessengerPage();
        initFeedbackFormPicker();
        bootStudentOnlyFloatingWidget();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
