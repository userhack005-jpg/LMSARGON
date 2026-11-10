<?php
    $bd        = $data ?? [];
    $modelFile = WRITEPATH . 'addons/extracted/lms_onboarding/Models/Addons/LmsOnboarding/LmsOnboardingModel.php';
    if (! class_exists(\App\Models\Addons\LmsOnboarding\LmsOnboardingModel::class) && is_file($modelFile)) {
        require_once $modelFile;
    }

    $onbModel   = new \App\Models\Addons\LmsOnboarding\LmsOnboardingModel();
    $coursesMap = is_array($coursesMap ?? null) && ! empty($coursesMap) ? $coursesMap : $onbModel->getEnrollableCoursesMap(true);

    $selCid = (int) ($bd['selected_course_id'] ?? ($_GET['course_id'] ?? 0));
    if ($selCid <= 0 && ! empty($coursesMap)) {
        $selCid = (int) array_key_first($coursesMap);
    }
    $lockedCourse = ($selCid > 0 && isset($coursesMap[$selCid])) ? $coursesMap[$selCid] : null;

    $loggedUser    = isset($loggedUser) ? $loggedUser : $onbModel->getLoggedInUserRecord();
    $isLogged      = ($loggedUser !== null);
    $loggedUid     = $isLogged ? (int) $loggedUser['id'] : 0;
    $loggedName    = $isLogged ? (string) ($loggedUser['display_name'] ?? 'Parent') : '';
    $loggedEmail   = $isLogged ? (string) ($loggedUser['email'] ?? '') : '';
    $loggedCountry = strtoupper(trim((string) (session()->get('user_country') ?: 'IN')));

    $existingKids   = isset($existingKids) && is_array($existingKids) ? $existingKids : (($isLogged && $loggedUid > 0) ? $onbModel->getParentLinkedChildren($loggedUid) : []);
    $myAppliedDemos = isset($myAppliedDemos) && is_array($myAppliedDemos) ? $myAppliedDemos : (($isLogged && $loggedUid > 0) ? $onbModel->getAppliedDemosForUser($loggedUid, $loggedEmail, array_keys($existingKids)) : []);

    $eligibleExistingCount = 0;
    foreach ($existingKids as $kId => $kInfo) {
        if (empty($kInfo['applied_course_ids'][$selCid])) {
            $eligibleExistingCount++;
        }
    }
?>
<link rel="stylesheet" href="<?= base_url('assets/addons/lms_onboarding/lms_onboarding.css?v=12.1') ?>">
<script src="<?= base_url('assets/addons/lms_onboarding/lms_onboarding.js?v=12.1') ?>" defer></script>

<section class="<?= esc($blockClass ?? 'cms-block') ?> <?= esc($blockScopeClass ?? '') ?> onb-booking-block is-revealed">
    <div class="onb-inner-container">

        <?php if (! empty($myAppliedDemos)): ?>
            <div class="console-card onb-applied-status-card">
                <div class="onb-flex-between" style="margin-bottom:0.85rem;">
                    <div>
                        <span class="onb-badge onb-badge-cyan">🎯 MY APPLIED DEMO CLASSES (<?= count($myAppliedDemos) ?>)</span>
                        <h3 style="margin:0.3rem 0 0; font-size:1.12rem;">Your Demo Class Applications &amp; Live Join Links</h3>
                    </div>
                </div>
                <div class="onb-applied-list">
                    <?php foreach ($myAppliedDemos as $ad): ?>
                        <?php
                            $st       = strtolower((string) ($ad['status'] ?? 'new_lead'));
                            $slotLive = ($st === 'demo_live' || ($ad['slot_live_status'] ?? '') === 'live');
                            $isSched  = ($st === 'demo_scheduled' || (int) ($ad['demo_batch_id'] ?? 0) > 0);
                            $isAtt    = ($st === 'demo_attended' || ($ad['attendance_status'] ?? '') === 'Present');
                            $isConv   = ($st === 'converted');
                            $joinLink = trim((string) ($ad['live_join_link'] ?? ''));
                        ?>
                        <div class="panel-card onb-applied-item" style="<?= $slotLive ? 'border-color:#06b6d4 !important;' : '' ?>">
                            <div>
                                <div style="font-weight:800; font-size:0.95rem;">
                                    🎓 <?= esc($ad['child_name']) ?> — <span class="text-emerald"><?= esc($ad['course_title']) ?></span>
                                </div>
                                <div class="mono-cell muted-cell" style="margin-top:0.25rem;">
                                    <?php if ($isSched && ! empty($ad['slot_date'])): ?>
                                        📅 Demo Date: <strong class="text-cyan"><?= esc($ad['slot_date']) ?></strong> •
                                        ⏰ Time: <strong class="text-cyan"><?= esc($ad['slot_time_ampm']) ?> IST</strong>
                                        <?php if (! empty($ad['teacher_name'])): ?>
                                            • 👩‍🏫 Teacher: <strong><?= esc($ad['teacher_name']) ?></strong>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        ⏰ Preferred Timing: <strong><?= esc($ad['preferred_time_slot']) ?></strong>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="onb-action-bar">
                                <?php if ($isConv): ?>
                                    <span class="onb-badge onb-badge-emerald">✅ Enrolled in Batch</span>
                                <?php elseif ($isAtt): ?>
                                    <span class="onb-badge onb-badge-emerald">✅ Demo Attended</span>
                                <?php elseif ($slotLive && $joinLink !== ''): ?>
                                    <span class="onb-badge onb-badge-cyan">🔴 LIVE NOW</span>
                                    <a href="<?= esc($joinLink) ?>" target="_blank" class="onb-btn onb-btn-emerald onb-btn-sm">
                                        🎥 Join Live Demo Class ↗
                                    </a>
                                    <button type="button" class="onb-btn onb-btn-outline onb-btn-sm js-onb-copy-link" data-link="<?= esc($joinLink) ?>">📋 Copy</button>
                                <?php elseif ($isSched): ?>
                                    <span class="onb-badge onb-badge-cyan">📅 Demo Slot Assigned</span>
                                    <?php if ($joinLink !== ''): ?>
                                        <a href="<?= esc($joinLink) ?>" target="_blank" class="onb-btn onb-btn-outline onb-btn-sm">🎥 Meeting Link ↗</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="onb-badge onb-badge-amber">⏳ Applied — Awaiting Admin Slot Assignment</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="console-card onb-wizard-form-card">
            <div style="margin-bottom: 1.25rem;">
                <span class="onb-badge onb-badge-emerald"><?= esc($bd['badge'] ?? 'FREE DEMO BOOKING') ?></span>
                <h2 style="margin:0.4rem 0 0.25rem; font-size:clamp(1.35rem, 2.8vw, 1.65rem);"><?= esc($bd['heading'] ?? 'Book a Free Demo Class') ?></h2>
                <?php if (! empty($bd['subheading'])): ?>
                    <p class="muted-cell" style="margin:0;"><?= esc($bd['subheading']) ?></p>
                <?php endif; ?>
            </div>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="onb-alert onb-alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="onb-alert onb-alert-error"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <form action="<?= base_url('demo/submit') ?>" method="post" class="js-onb-booking-form">
                <?= csrf_field() ?>
                <input type="hidden" name="course_node_id" value="<?= $selCid ?>" class="js-onb-main-course-id">
                <input type="hidden" name="timezone" class="js-onb-auto-tz" value="Asia/Kolkata">
                <input type="hidden" name="country_code" value="<?= esc($loggedCountry) ?>">

                <?php if ($isLogged && $loggedUid > 0): ?>
                    <input type="hidden" name="parent_name" value="<?= esc($loggedName) ?>">
                    <input type="hidden" name="parent_email" value="<?= esc($loggedEmail) ?>">
                    <div class="panel-card onb-parent-session-bar">
                        <div>
                            <span class="mono-label text-emerald">LOGGED-IN PARENT ACCOUNT</span>
                            <div style="font-weight:800; font-size:0.96rem; margin-top:0.15rem;">
                                👨‍👩‍👧‍👦 <?= esc($loggedName) ?>
                                <?php if ($loggedEmail !== ''): ?>
                                    <small class="muted-cell">(<?= esc($loggedEmail) ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="onb-badge onb-badge-cyan">✓ Session Active</span>
                    </div>
                <?php else: ?>
                    <div class="onb-form-grid-2">
                        <div class="field-group">
                            <label class="field-label"><span>Parent / Guardian Full Name *</span></label>
                            <input type="text" name="parent_name" class="field-input" placeholder="Your Full Name" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Parent Email Address *</span></label>
                            <input type="email" name="parent_email" class="field-input" placeholder="parent@example.com" required>
                        </div>
                    </div>
                    <div class="onb-form-grid-2">
                        <div class="field-group">
                            <label class="field-label"><span>WhatsApp / Mobile Number *</span></label>
                            <input type="text" name="parent_phone" class="field-input" placeholder="Mobile Number" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span class="text-emerald">Create Password (For Account Access) *</span></label>
                            <input type="password" name="parent_password" class="field-input" placeholder="Choose a password (min 4 chars)" minlength="4" required>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="js-onb-children-container" style="display:flex; flex-direction:column; gap:0.85rem; margin-bottom:0.85rem;">
                    <div class="panel-card js-onb-child-row onb-child-card">
                        <div class="onb-flex-between" style="margin-bottom:0.6rem;">
                            <strong class="text-emerald js-child-badge-label" style="font-size:0.88rem;">🎓 Child #1</strong>
                            <?php if ($lockedCourse): ?>
                                <span class="onb-badge onb-badge-cyan">Course: <?= esc($lockedCourse['title']) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($lockedCourse): ?>
                            <input type="hidden" name="children[0][course_id]" value="<?= $selCid ?>" class="js-row-course-select">
                        <?php else: ?>
                            <div class="field-group" style="margin-bottom:0.65rem;">
                                <label class="field-label"><span>Select Course / Level (Demo Available) *</span></label>
                                <select name="children[0][course_id]" class="field-select js-row-course-select" required>
                                    <?php foreach ($coursesMap as $cid => $cInfo): ?>
                                        <option value="<?= (int) $cid ?>" <?= $selCid === (int) $cid ? 'selected' : '' ?>>
                                            <?= esc($cInfo['breadcrumb_path']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if (! empty($existingKids)): ?>
                            <div class="field-group" style="margin-bottom:0.65rem;">
                                <label class="field-label"><span>Select Child From Your Account or Add a New Child *</span></label>
                                <select name="children[0][existing_student_id]" class="field-select js-existing-kid-select">
                                    <?php $firstEligibleSelected = false; ?>
                                    <?php foreach ($existingKids as $kId => $kInfo): ?>
                                        <?php
                                            $appliedCsv  = implode(',', array_keys($kInfo['applied_course_ids'] ?? []));
                                            $alreadyThis = ! empty($kInfo['applied_course_ids'][$selCid]);
                                            $selAttr     = '';
                                            if (! $alreadyThis && ! $firstEligibleSelected) {
                                                $selAttr = 'selected';
                                                $firstEligibleSelected = true;
                                            }
                                        ?>
                                        <option value="<?= (int) $kId ?>"
                                                data-applied-courses="<?= esc($appliedCsv) ?>"
                                                data-base-label="🎓 <?= esc($kInfo['full_name']) ?> (Age <?= (int) $kInfo['age'] ?>)"
                                                <?= $alreadyThis ? 'disabled' : '' ?>
                                                <?= $selAttr ?>>
                                            🎓 <?= esc($kInfo['full_name']) ?> (Age <?= (int) $kInfo['age'] ?>)<?= $alreadyThis ? ' — ✓ Already Applied for this Course Demo' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="0" <?= $eligibleExistingCount === 0 ? 'selected' : '' ?>>➕ Add a New Child to My Account...</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="children[0][existing_student_id]" value="0">
                        <?php endif; ?>

                        <div class="onb-form-grid-2 js-new-kid-fields <?= ($eligibleExistingCount > 0) ? 'is-hidden' : '' ?>">
                            <div class="field-group" style="margin:0;">
                                <label class="field-label"><span>New Child's Full Name *</span></label>
                                <input type="text" name="children[0][name]" class="field-input js-new-kid-name" placeholder="Enter Child's Full Name" <?= ($eligibleExistingCount === 0) ? 'required' : '' ?>>
                            </div>
                            <div class="field-group" style="margin:0;">
                                <label class="field-label"><span>Child's Age (Years) *</span></label>
                                <input type="number" name="children[0][age]" class="field-input mono-input" value="8" min="4" max="18">
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 1.15rem;">
                    <button type="button" class="onb-btn onb-btn-outline onb-btn-sm js-onb-add-child-btn">➕ Add Another Child</button>
                </div>

                <div class="field-group">
                    <label class="field-label"><span>Preferred Demo Timing Slot (AM / PM) *</span></label>
                    <select name="preferred_time_slot" class="field-select" required>
                        <option value="09:00 AM - 10:00 AM">🕘 09:00 AM – 10:00 AM</option>
                        <option value="10:00 AM - 11:00 AM">🕙 10:00 AM – 11:00 AM</option>
                        <option value="11:00 AM - 12:00 PM">🕚 11:00 AM – 12:00 PM</option>
                        <option value="12:00 PM - 01:00 PM">🕛 12:00 PM – 01:00 PM</option>
                        <option value="03:00 PM - 04:00 PM">🕒 03:00 PM – 04:00 PM</option>
                        <option value="04:00 PM - 05:00 PM">🕓 04:00 PM – 05:00 PM</option>
                        <option value="05:00 PM - 06:00 PM" selected>🕔 05:00 PM – 06:00 PM</option>
                        <option value="06:00 PM - 07:00 PM">🕕 06:00 PM – 07:00 PM</option>
                        <option value="07:00 PM - 08:00 PM">🕖 07:00 PM – 08:00 PM</option>
                        <option value="08:00 PM - 09:00 PM">🕗 08:00 PM – 09:00 PM</option>
                    </select>
                </div>

                <button type="submit" class="onb-btn onb-btn-emerald onb-btn-lg js-onb-submit-btn" style="width:100%;">
                    🎯 Apply for Free Demo Class →
                </button>
            </form>
        </div>
    </div>
</section>
