(function () {
    'use strict';
    if (window.__CMS_LMS_FINANCE_V81__) return;
    window.__CMS_LMS_FINANCE_V81__ = true;

    function initCustomParentChildBilling() {
        var parentSel = document.getElementById('js-cb-parent-select');
        var stuSel    = document.getElementById('js-cb-student-select');
        var currInp   = document.getElementById('js-cb-currency');
        var ctryInp   = document.getElementById('js-cb-country');
        var subInp    = document.getElementById('js-cb-subtotal');
        var taxInp    = document.getElementById('js-cb-tax');
        var totPrev   = document.getElementById('js-cb-total-preview');

        if (!parentSel || !stuSel) return;

        function recalcTotal() {
            var c = currInp ? currInp.value : 'INR';
            var s = parseFloat(subInp && subInp.value ? subInp.value : '0') || 0;
            var t = parseFloat(taxInp && taxInp.value ? taxInp.value : '0') || 0;
            var tot = s + (s * (t / 100));
            if (totPrev) totPrev.textContent = c + ' ' + tot.toFixed(2);
        }

        parentSel.addEventListener('change', function () {
            var pid  = parseInt(parentSel.value || '0', 10);
            var pOpt = parentSel.options[parentSel.selectedIndex];
            if (pOpt && pOpt.getAttribute('data-currency')) {
                if (currInp) currInp.value = pOpt.getAttribute('data-currency');
                if (ctryInp) ctryInp.value = pOpt.getAttribute('data-country') || 'IN';
            }

            var firstMatch = '';
            Array.from(stuSel.options).forEach(function (opt) {
                if (!opt.value) return;
                var stuParent = parseInt(opt.getAttribute('data-parent') || '0', 10);
                var show = (pid === 0 || stuParent === pid);
                opt.style.display = show ? '' : 'none';
                if (show && !firstMatch) firstMatch = opt.value;
            });
            stuSel.value = firstMatch;
            recalcTotal();
        });

        stuSel.addEventListener('change', function () {
            var sOpt = stuSel.options[stuSel.selectedIndex];
            if (sOpt && sOpt.getAttribute('data-currency')) {
                if (currInp) currInp.value = sOpt.getAttribute('data-currency');
                if (ctryInp) ctryInp.value = sOpt.getAttribute('data-country') || 'IN';
            }
            recalcTotal();
        });

        if (subInp) subInp.addEventListener('input', recalcTotal);
        if (taxInp) taxInp.addEventListener('input', recalcTotal);
    }

    function initParentPortalHandlers() {
        var filterBar = document.getElementById('js-lfn-child-filter-bar');
        if (filterBar) {
            filterBar.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-lfn-child-tab');
                if (!btn) return;
                filterBar.querySelectorAll('.js-lfn-child-tab').forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
                var cid = btn.getAttribute('data-child-id') || 'all';
                document.querySelectorAll('.js-lfn-child-item').forEach(function (el) {
                    el.style.display = (cid === 'all' || el.getAttribute('data-child-id') === cid) ? '' : 'none';
                });
            });
        }

        var offModal = document.getElementById('js-offline-proof-modal');
        var offClose = document.getElementById('js-off-modal-close');
        document.querySelectorAll('.js-open-offline-modal').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!offModal) return;
                document.getElementById('off_stu_id').value  = btn.getAttribute('data-student-id') || '0';
                document.getElementById('off_sub_id').value  = btn.getAttribute('data-subscription-id') || '0';
                document.getElementById('off_inv_id').value  = btn.getAttribute('data-invoice-id') || '0';
                document.getElementById('off_node_id').value = btn.getAttribute('data-node-id') || '0';
                var tEl = document.getElementById('js-off-modal-title');
                if (tEl) {
                    tEl.textContent = btn.getAttribute('data-title') + ' (' + btn.getAttribute('data-currency') + ' ' + btn.getAttribute('data-amount') + ')';
                }
                offModal.classList.remove('is-hidden');
            });
        });
        if (offClose && offModal) {
            offClose.addEventListener('click', function () { offModal.classList.add('is-hidden'); });
        }

        var portalRoot = document.getElementById('js-lfn-portal-root');
        var verifyForm = document.getElementById('js-rzp-verify-form');

        document.querySelectorAll('.js-rzp-pay-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!portalRoot || !verifyForm) return;
                var orderUrl = portalRoot.getAttribute('data-order-url');
                var stuId    = btn.getAttribute('data-student-id') || '0';
                var subId    = btn.getAttribute('data-subscription-id') || '0';
                var invId    = btn.getAttribute('data-invoice-id') || '0';
                var nodeId   = btn.getAttribute('data-node-id') || '0';
                var title    = btn.getAttribute('data-title') || 'Course Fee';
                var amount   = btn.getAttribute('data-amount') || '1000';
                var currency = btn.getAttribute('data-currency') || 'INR';

                document.getElementById('rzp_stu_id').value   = stuId;
                document.getElementById('rzp_sub_id').value   = subId;
                document.getElementById('rzp_inv_id').value   = invId;
                document.getElementById('rzp_node_id').value  = nodeId;
                document.getElementById('rzp_amount').value   = amount;
                document.getElementById('rzp_currency').value = currency;

                var fd = new FormData();
                fd.append('amount', amount);
                fd.append('currency', currency);
                var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (csrfMeta) fd.append('csrf_test_name', csrfMeta.getAttribute('content'));

                btn.disabled = true;
                fetch(orderUrl, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd
                })
                .then(function (r) { return r.json(); })
                .then(function (ord) {
                    btn.disabled = false;
                    if (typeof window.Razorpay === 'function' && ord && ord.key_id) {
                        var rzpOpts = {
                            key: ord.key_id,
                            amount: ord.subunit,
                            currency: ord.currency,
                            name: 'Academy Fee Checkout',
                            description: title,
                            handler: function (response) {
                                document.getElementById('rzp_payment_id').value = response.razorpay_payment_id || '';
                                document.getElementById('rzp_order_id').value   = response.razorpay_order_id || '';
                                document.getElementById('rzp_signature').value  = response.razorpay_signature || '';
                                verifyForm.submit();
                            }
                        };
                        if (ord.order_id) rzpOpts.order_id = ord.order_id;
                        var rzpInst = new window.Razorpay(rzpOpts);
                        rzpInst.open();
                    } else {
                        if (window.confirm('Razorpay Sandbox Checkout:\n\nConfirm payment of ' + currency + ' ' + amount + ' for "' + title + '"?')) {
                            document.getElementById('rzp_payment_id').value = 'pay_sandbox_' + Date.now();
                            verifyForm.submit();
                        }
                    }
                })
                .catch(function () {
                    btn.disabled = false;
                });
            });
        });

        var enrollMethod = document.getElementById('js-enroll-pay-method');
        var enrollOffBox = document.getElementById('js-enroll-offline-fields');
        if (enrollMethod && enrollOffBox) {
            enrollMethod.addEventListener('change', function () {
                enrollOffBox.classList.toggle('is-hidden', enrollMethod.value !== 'offline_proof');
            });
        }
    }

    function initAll() {
        initCustomParentChildBilling();
        initParentPortalHandlers();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
