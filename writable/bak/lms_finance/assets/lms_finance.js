(function () {
    'use strict';
    if (window.__CMS_LMS_FINANCE_V121__) return;
    window.__CMS_LMS_FINANCE_V121__ = true;

    function escapeHtml(str) {
        return String(str == null ? '' : str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function openRealRazorpayCheckout(opts) {
        if (typeof window.Razorpay !== 'function') {
            window.alert('Razorpay Checkout SDK (https://checkout.razorpay.com/v1/checkout.js) failed to load.');
            return;
        }
        if (!opts.key_id || !opts.order_id) {
            window.alert(opts.errorMessage || 'Razorpay Order ID or Key ID is missing.');
            return;
        }

        var rzpOptions = {
            key: opts.key_id,
            amount: opts.subunit,
            currency: opts.currency || 'INR',
            name: 'Online Abacus Academy',
            description: opts.title || 'Course Tuition Fee',
            order_id: opts.order_id,
            prefill: {
                name: opts.payer_name || '',
                email: opts.payer_email || '',
                contact: opts.payer_phone || ''
            },
            theme: { color: '#10b981' },
            handler: function (response) {
                if (typeof opts.onSuccess === 'function') {
                    opts.onSuccess({
                        razorpay_payment_id: response.razorpay_payment_id || '',
                        razorpay_order_id: response.razorpay_order_id || opts.order_id,
                        razorpay_signature: response.razorpay_signature || ''
                    });
                }
            }
        };

        var rzpInstance = new window.Razorpay(rzpOptions);
        rzpInstance.on('payment.failed', function (resp) {
            var reason = (resp && resp.error && resp.error.description) ? resp.error.description : 'Payment failed or was cancelled.';
            window.alert('Razorpay Payment Failed: ' + reason);
        });
        rzpInstance.open();
    }

    function syncEnrollChildRowDropdown(row) {
        if (!row) return;
        var sel     = row.querySelector('.js-enroll-existing-kid-select');
        var newBox  = row.querySelector('.js-enroll-new-kid-fields');
        var nameInp = row.querySelector('.js-enroll-kid-name');
        if (!sel || !newBox) return;

        var isNew = (String(sel.value) === '0');
        newBox.classList.toggle('is-hidden', !isNew);
        if (nameInp) nameInp.required = isNew;
    }

    function initDirectEnrollmentCheckout() {
        var rootEl     = document.getElementById('js-lfn-enroll-root');
        var enrollForm = document.getElementById('js-direct-enroll-form');
        if (!rootEl || !enrollForm) return;

        var addChildBtn  = document.getElementById('js-enroll-add-child-btn');
        var childrenList = document.getElementById('js-enroll-children-list');
        var summaryBox   = document.getElementById('js-enroll-summary-items');
        var grandTotalEl = document.getElementById('js-enroll-grand-total');
        var payMethodSel = document.getElementById('js-enroll-pay-method');
        var offFieldsBox = document.getElementById('js-enroll-offline-fields');
        var submitBtn    = document.getElementById('js-enroll-submit-btn');
        var errBanner    = document.getElementById('js-lfn-rzp-error-banner');

        var orderUrl    = rootEl.getAttribute('data-order-url');
        var currCode    = rootEl.getAttribute('data-currency') || 'INR';
        var currSym     = rootEl.getAttribute('data-symbol') || 'â‚¹';
        var unitFee     = parseFloat(rootEl.getAttribute('data-unit-fee') || '1000') || 1000;
        var fixedCourse = rootEl.getAttribute('data-course-title') || 'Course';
        var isVerifiedToSubmit = false;

        function recalcEnrollmentSummary() {
            var rows        = childrenList.querySelectorAll('.js-enroll-child-row');
            var total       = 0;
            var summaryHtml = '';

            rows.forEach(function (row, idx) {
                var lbl = row.querySelector('.js-enroll-kid-label');
                if (lbl) lbl.textContent = 'ðŸŽ“ Child #' + (idx + 1);

                row.querySelectorAll('input, select').forEach(function (inp) {
                    var nm = inp.getAttribute('name') || '';
                    if (nm.indexOf('children[') !== -1) {
                        inp.setAttribute('name', nm.replace(/children\[\d+\]/, 'children[' + idx + ']'));
                    }
                });

                var exSel   = row.querySelector('.js-enroll-existing-kid-select');
                var nameInp = row.querySelector('.js-enroll-kid-name');
                var kidName = 'Child #' + (idx + 1);

                if (exSel && String(exSel.value) !== '0') {
                    var selOpt = exSel.options[exSel.selectedIndex];
                    kidName = (selOpt && selOpt.getAttribute('data-name')) ? selOpt.getAttribute('data-name') : selOpt.textContent;
                } else if (nameInp && nameInp.value.trim() !== '') {
                    kidName = nameInp.value.trim();
                }

                total += unitFee;
                summaryHtml += '<div style="display:flex; justify-content:space-between; font-size:0.86rem; padding:0.45rem 0.65rem; border-radius:8px; background:var(--bg-canvas);">' +
                    '<span>ðŸŽ“ <strong>' + escapeHtml(kidName) + '</strong> â€” ' + escapeHtml(fixedCourse) + '</span>' +
                    '<strong class="mono-cell text-emerald">' + escapeHtml(currSym) + unitFee.toFixed(2) + '</strong>' +
                    '</div>';
            });

            if (summaryBox) summaryBox.innerHTML = summaryHtml;
            if (grandTotalEl) grandTotalEl.innerHTML = escapeHtml(currSym) + total.toFixed(2) + ' <small>' + escapeHtml(currCode) + '</small>';
            return total;
        }

        childrenList.querySelectorAll('.js-enroll-child-row').forEach(syncEnrollChildRowDropdown);

        if (addChildBtn) {
            addChildBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var rows    = childrenList.querySelectorAll('.js-enroll-child-row');
                var nextIdx = rows.length;
                var clone   = rows[0].cloneNode(true);

                var topBar = clone.querySelector('.js-enroll-kid-label').parentElement;
                var oldRm  = topBar.querySelector('.js-rm-enroll-kid');
                if (oldRm) oldRm.remove();

                var rmBtn = document.createElement('button');
                rmBtn.type = 'button';
                rmBtn.className = 'btn-action text-danger js-rm-enroll-kid';
                rmBtn.textContent = 'âœ• Remove';
                rmBtn.style.fontSize = '0.75rem';
                rmBtn.addEventListener('click', function () {
                    clone.remove();
                    recalcEnrollmentSummary();
                });
                topBar.appendChild(rmBtn);

                clone.querySelectorAll('input, select').forEach(function (inp) {
                    var nm = inp.getAttribute('name') || '';
                    inp.setAttribute('name', nm.replace(/children\[\d+\]/, 'children[' + nextIdx + ']'));
                    if (inp.tagName === 'INPUT' && inp.type === 'text') inp.value = '';
                    if (inp.classList.contains('js-enroll-existing-kid-select')) inp.value = '0';
                });

                childrenList.appendChild(clone);
                syncEnrollChildRowDropdown(clone);
                recalcEnrollmentSummary();
            });
        }

        childrenList.addEventListener('change', function (e) {
            var sel = e.target.closest('.js-enroll-existing-kid-select');
            if (sel) syncEnrollChildRowDropdown(sel.closest('.js-enroll-child-row'));
            recalcEnrollmentSummary();
        });
        childrenList.addEventListener('input', recalcEnrollmentSummary);
        recalcEnrollmentSummary();

        if (payMethodSel && offFieldsBox) {
            payMethodSel.addEventListener('change', function () {
                var isOff = (payMethodSel.value === 'offline_proof');
                offFieldsBox.classList.toggle('is-hidden', !isOff);
                document.getElementById('js-enroll-off-utr').required  = isOff;
                document.getElementById('js-enroll-off-file').required = isOff;
            });
        }

        enrollForm.addEventListener('submit', function (e) {
            if (isVerifiedToSubmit) return;
            var method = payMethodSel ? payMethodSel.value : 'razorpay';
            if (method === 'offline_proof') return;

            e.preventDefault();
            if (errBanner) errBanner.classList.add('is-hidden');

            var totalAmount = recalcEnrollmentSummary();
            var pNameEl     = document.getElementById('js-enroll-parent-name');
            var pEmailEl    = document.getElementById('js-enroll-parent-email');
            var pPhoneEl    = document.getElementById('js-enroll-parent-phone');

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'â³ Creating Razorpay Order...';
            }

            var fd = new FormData();
            fd.append('amount', totalAmount);
            fd.append('currency', currCode);
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta) fd.append('csrf_test_name', csrfMeta.getAttribute('content'));

            fetch(orderUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd
            })
            .then(function (r) { return r.json(); })
            .then(function (ord) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'ðŸ”’ Pay Now via Razorpay & Enroll â†’';
                }
                if (ord && ord.csrf_token && csrfMeta) csrfMeta.setAttribute('content', ord.csrf_token);
                if (!ord || !ord.ok || !ord.order_id) {
                    var msg = (ord && ord.message) ? ord.message : 'Unable to create Razorpay Order.';
                    if (errBanner) {
                        errBanner.textContent = msg;
                        errBanner.classList.remove('is-hidden');
                    } else {
                        window.alert(msg);
                    }
                    return;
                }

                openRealRazorpayCheckout({
                    key_id: ord.key_id,
                    order_id: ord.order_id,
                    amount: ord.amount,
                    subunit: ord.subunit,
                    currency: ord.currency || currCode,
                    title: fixedCourse + ' (' + childrenList.querySelectorAll('.js-enroll-child-row').length + ' Child)',
                    payer_name: pNameEl ? pNameEl.value.trim() : '',
                    payer_email: pEmailEl ? pEmailEl.value.trim() : '',
                    payer_phone: pPhoneEl ? pPhoneEl.value.trim() : '',
                    onSuccess: function (res) {
                        document.getElementById('js-enroll-rzp-pay-id').value = res.razorpay_payment_id;
                        document.getElementById('js-enroll-rzp-ord-id').value = res.razorpay_order_id;
                        document.getElementById('js-enroll-rzp-sig').value    = res.razorpay_signature;
                        isVerifiedToSubmit = true;
                        enrollForm.submit();
                    }
                });
            })
            .catch(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'ðŸ”’ Pay Now via Razorpay & Enroll â†’';
                }
            });
        });
    }

    function initCustomParentChildBilling() {
        var parentSel = document.getElementById('js-cb-parent-select');
        var stuSel    = document.getElementById('js-cb-student-select');
        var currSel   = document.getElementById('js-cb-currency');
        var ctryInp   = document.getElementById('js-cb-country');
        var subInp    = document.getElementById('js-cb-subtotal');
        var taxInp    = document.getElementById('js-cb-tax');
        var totPrev   = document.getElementById('js-cb-total-preview');

        if (!parentSel || !stuSel) return;

        function setCurrencyDropdownValue(currCode) {
            if (!currSel || !currCode) return;
            var code = String(currCode).toUpperCase().trim();
            var exists = Array.from(currSel.options).some(function (opt) { return opt.value === code; });
            if (!exists) {
                var newOpt = document.createElement('option');
                newOpt.value = code;
                newOpt.textContent = code;
                currSel.appendChild(newOpt);
            }
            currSel.value = code;
        }

        function recalcTotal() {
            var c   = currSel ? currSel.value : 'INR';
            var s   = parseFloat(subInp && subInp.value ? subInp.value : '0') || 0;
            var t   = parseFloat(taxInp && taxInp.value ? taxInp.value : '0') || 0;
            var tot = s + (s * (t / 100));
            if (totPrev) totPrev.textContent = c + ' ' + tot.toFixed(2);
        }

        parentSel.addEventListener('change', function () {
            var pid  = parseInt(parentSel.value || '0', 10);
            var pOpt = parentSel.options[parentSel.selectedIndex];
            if (pOpt && pOpt.getAttribute('data-currency')) {
                setCurrencyDropdownValue(pOpt.getAttribute('data-currency'));
                if (ctryInp) ctryInp.value = pOpt.getAttribute('data-country') || 'IN';
            }

            var firstMatch = '';
            Array.from(stuSel.options).forEach(function (opt) {
                if (!opt.value) return;
                var stuParent = parseInt(opt.getAttribute('data-parent') || '0', 10);
                var show      = (pid === 0 || stuParent === pid);
                opt.style.display = show ? '' : 'none';
                if (show && !firstMatch) firstMatch = opt.value;
            });
            stuSel.value = firstMatch;
            if (firstMatch) {
                var sOpt = stuSel.options[stuSel.selectedIndex];
                if (sOpt && sOpt.getAttribute('data-currency')) {
                    setCurrencyDropdownValue(sOpt.getAttribute('data-currency'));
                    if (ctryInp) ctryInp.value = sOpt.getAttribute('data-country') || 'IN';
                }
            }
            recalcTotal();
        });

        stuSel.addEventListener('change', function () {
            var sOpt = stuSel.options[stuSel.selectedIndex];
            if (sOpt && sOpt.getAttribute('data-currency')) {
                setCurrencyDropdownValue(sOpt.getAttribute('data-currency'));
                if (ctryInp) ctryInp.value = sOpt.getAttribute('data-country') || 'IN';
            }
            recalcTotal();
        });

        if (currSel) currSel.addEventListener('change', recalcTotal);
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
                if (tEl) tEl.textContent = btn.getAttribute('data-title') + ' (' + btn.getAttribute('data-currency') + ' ' + btn.getAttribute('data-amount') + ')';
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

                var origText = btn.textContent;
                btn.disabled = true;
                btn.textContent = 'â³ Creating Order...';

                fetch(orderUrl, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd
                })
                .then(function (r) { return r.json(); })
                .then(function (ord) {
                    btn.disabled = false;
                    btn.textContent = origText;
                    if (ord && ord.csrf_token && csrfMeta) csrfMeta.setAttribute('content', ord.csrf_token);
                    if (!ord || !ord.ok || !ord.order_id) {
                        window.alert((ord && ord.message) ? ord.message : 'Unable to create Razorpay Order.');
                        return;
                    }

                    openRealRazorpayCheckout({
                        key_id: ord.key_id,
                        order_id: ord.order_id,
                        amount: ord.amount,
                        subunit: ord.subunit,
                        currency: ord.currency || currency,
                        title: title,
                        onSuccess: function (res) {
                            document.getElementById('rzp_payment_id').value = res.razorpay_payment_id;
                            document.getElementById('rzp_order_id').value   = res.razorpay_order_id;
                            document.getElementById('rzp_signature').value  = res.razorpay_signature;
                            verifyForm.submit();
                        }
                    });
                })
                .catch(function () {
                    btn.disabled = false;
                    btn.textContent = origText;
                });
            });
        });
    }

    initDirectEnrollmentCheckout();
    initCustomParentChildBilling();
    initParentPortalHandlers();
})();
