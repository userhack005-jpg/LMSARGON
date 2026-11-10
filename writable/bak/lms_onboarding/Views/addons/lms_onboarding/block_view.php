<?php
    $bd        = $data ?? [];
    $modelFile = WRITEPATH . 'addons/extracted/lms_onboarding/Models/Addons/LmsOnboarding/LmsOnboardingModel.php';
    if (! class_exists(\App\Models\Addons\LmsOnboarding\LmsOnboardingModel::class) && is_file($modelFile)) {
        require_once $modelFile;
    }

    $onbModel = new \App\Models\Addons\LmsOnboarding\LmsOnboardingModel();
    if (! isset($coursesMap) || ! is_array($coursesMap)) {
        $coursesMap = $onbModel->getEnrollableCoursesMap();
    }

    $selCid        = (int) ($bd['selected_course_id'] ?? ($_GET['course_id'] ?? 0));
    $lockedCourse  = ($selCid > 0 && isset($coursesMap[$selCid])) ? $coursesMap[$selCid] : null;

    // Reliably detect logged-in Parent/User directly from Session + `users` DB table!
    $loggedUser    = $onbModel->getLoggedInUserRecord();
    $isLogged      = ($loggedUser !== null);
    $loggedUid     = $isLogged ? (int) $loggedUser['id'] : 0;
    $loggedName    = $isLogged ? (string) ($loggedUser['display_name'] ?? 'Parent') : '';
    $loggedEmail   = $isLogged ? (string) ($loggedUser['email'] ?? '') : '';
    $loggedCountry = strtoupper(trim((string) (session()->get('user_country') ?: 'IN')));

    $existingKids   = ($isLogged && $loggedUid > 0) ? $onbModel->getParentLinkedChildren($loggedUid) : [];
    $myAppliedDemos = ($isLogged && $loggedUid > 0) ? $onbModel->getAppliedDemosForUser($loggedUid, $loggedEmail, array_keys($existingKids)) : [];
?>
<link rel="stylesheet" href="<?= base_url('addons/lms-onboarding/assets/lms_onboarding.css') ?>">
<script src="<?= base_url('addons/lms-onboarding/assets/lms_onboarding.js') ?>" defer></script>

<section class="<?= esc($blockClass ?? 'cms-block') ?> <?= esc($blockScopeClass ?? '') ?> onb-booking-block is-revealed">
    <div class="onb-inner-container">

        <!-- 1. Applied Demo Classes Status Tracker (Visible immediately when Parent/User has applied!) -->
        <?php if (! empty($myAppliedDemos)): ?>
            <div class="console-card onb-applied-status-card">
                <div class="onb-flex-between" style="margin-bottom:0.85rem;">
                    <div>
                        <span class="role-badge text-cyan">🎯 MY APPLIED DEMO CLASSES (<?= count($myAppliedDemos) ?>)</span>
                        <h3 style="margin:0.3rem 0 0; font-size:1.12rem;">Your Demo Class Application &amp; Schedule Status</h3>
                    </div>
                </div>
                <div class="onb-applied-list">
                    <?php foreach ($myAppliedDemos as $ad): ?>
                        <?php
                            $st      = strtolower((string) ($ad['status'] ?? 'new_lead'));
                            $isSched = ($st === 'demo_scheduled' || (int) ($ad['assigned_teacher_id'] ?? 0) > 0);
                            $isConv  = ($st === 'converted');
                        ?>
                        <div class="panel-card onb-applied-item">
                            <div>
                                <div style="font-weight:800; font-size:0.96rem;">
                                    🎓 <?= esc($ad['child_name']) ?> — <span class="text-emerald"><?= esc($ad['course_title']) ?></span>
                                </div>
                                <div class="mono-cell muted-cell" style="margin-top:0.2rem;">
                                    ⏰ Preferred Timing: <strong><?= esc($ad['preferred_time_slot']) ?></strong>
                                    <?php if ($isSched && ! empty($ad['preferred_date'])): ?>
                                        • 📅 Scheduled Date: <strong class="text-cyan"><?= esc($ad['preferred_date']) ?></strong>
                                    <?php endif; ?>
                                    <?php if (! empty($ad['teacher_name'])): ?>
                                        • 👩‍🏫 Teacher: <strong><?= esc($ad['teacher_name']) ?></strong>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                                <?php if ($isConv): ?>
                                    <span class="role-badge text-emerald">✅ Added to Live Batch</span>
                                <?php elseif ($isSched): ?>
                                    <span class="role-badge text-cyan">📅 Demo Scheduled</span>
                                    <?php if (! empty($ad['meeting_link'])): ?>
                                        <a href="<?= esc($ad['meeting_link']) ?>" target="_blank" class="btn-primary btn-sm" style="text-decoration:none;">🎥 Join Demo Class ↗</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="role-badge text-amber">⏳ Applied — Admin Will Schedule &amp; Respond</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 2. Main Multi-Child Demo Booking Card -->
        <div class="console-card onb-wizard-form-card">
            <div style="margin-bottom: 1.25rem;">
                <span class="role-badge text-emerald"><?= esc($bd['badge'] ?? 'FREE DEMO BOOKING') ?></span>
                <h2 style="margin:0.4rem 0 0.25rem; font-size:1.55rem;"><?= esc($bd['heading'] ?? 'Book a Free Demo Class') ?></h2>
                <?php if (! empty($bd['subheading'])): ?>
                    <p class="muted-cell" style="margin:0;"><?= esc($bd['subheading']) ?></p>
                <?php endif; ?>
            </div>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert-banner alert-success" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert-banner alert-error" style="margin-bottom:1rem;"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>

            <form action="<?= base_url('demo/submit') ?>" method="post" class="js-onb-booking-form">
                <?= csrf_field() ?>
                <input type="hidden" name="course_node_id" value="<?= $selCid ?>">
                <input type="hidden" name="timezone" class="js-onb-auto-tz" value="Asia/Kolkata">
                <input type="hidden" name="country_code" value="<?= esc($loggedCountry) ?>">

                <?php if ($isLogged && $loggedUid > 0): ?>
                    <!-- Logged-in Parent: NEVER ask for Parent Name, Email, Phone, or Password again! -->
                    <input type="hidden" name="parent_name" value="<?= esc($loggedName) ?>">
                    <input type="hidden" name="parent_email" value="<?= esc($loggedEmail) ?>">
                    <div class="panel-card onb-parent-session-bar">
                        <div>
                            <span class="mono-label text-emerald">LOGGED-IN PARENT ACCOUNT</span>
                            <div style="font-weight:800; font-size:0.98rem; margin-top:0.15rem;">
                                👨‍👩‍👧‍👦 <?= esc($loggedName) ?>
                                <?php if ($loggedEmail !== ''): ?>
                                    <small class="muted-cell">(<?= esc($loggedEmail) ?>)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="role-badge text-cyan">✓ Session Active</span>
                    </div>
                <?php else: ?>
                    <div class="grid-2-col">
                        <div class="field-group">
                            <label class="field-label"><span>Parent / Guardian Full Name *</span></label>
                            <input type="text" name="parent_name" class="field-input" placeholder="Your Full Name" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label"><span>Parent Email Address *</span></label>
                            <input type="email" name="parent_email" class="field-input" placeholder="parent@example.com" required>
                        </div>
                    </div>
                    <div class="grid-2-col">
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

                <!-- Multi-Child Repeater Container -->
                <div class="js-onb-children-container" style="display:flex; flex-direction:column; gap:0.85rem; margin-bottom:0.85rem;">
                    <div class="panel-card js-onb-child-row onb-child-card">
                        <div class="onb-flex-between" style="margin-bottom:0.6rem;">
                            <strong class="text-emerald js-child-badge-label" style="font-size:0.88rem;">🎓 Child #1</strong>
                            <?php if ($lockedCourse): ?>
                                <span class="role-badge text-cyan">Course: <?= esc($lockedCourse['title']) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($lockedCourse): ?>
                            <input type="hidden" name="children[0][course_id]" value="<?= $selCid ?>">
                        <?php endif; ?>

                        <?php if (! empty($existingKids)): ?>
                            <div class="field-group" style="margin-bottom:0.65rem;">
                                <label class="field-label"><span>Select Child From Your Account or Add a New Child *</span></label>
                                <select name="children[0][existing_student_id]" class="field-select js-existing-kid-select">
                                    <?php foreach ($existingKids as $kId => $kInfo): ?>
                                        <option value="<?= (int) $kId ?>">🎓 <?= esc($kInfo['full_name']) ?> (Age <?= (int) $kInfo['age'] ?>)</option>
                                    <?php endforeach; ?>
                                    <option value="0">➕ Add a New Child to My Account...</option>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="children[0][existing_student_id]" value="0">
                        <?php endif; ?>

                        <div class="grid-2-col js-new-kid-fields <?= ! empty($existingKids) ? 'is-hidden' : '' ?>">
                            <div class="field-group" style="margin:0;">
                                <label class="field-label"><span>Child's Full Name *</span></label>
                                <input type="text" name="children[0][name]" class="field-input js-new-kid-name" placeholder="Enter Child's Full Name" <?= empty($existingKids) ? 'required' : '' ?>>
                            </div>
                            <div class="field-group" style="margin:0;">
                                <label class="field-label"><span>Child's Age (Years) *</span></label>
                                <input type="number" name="children[0][age]" class="field-input mono-input" value="8" min="4" max="18">
                            </div>
                        </div>

                        <?php if (! $lockedCourse): ?>
                            <div class="field-group" style="margin-top:0.65rem; margin-bottom:0;">
                                <label class="field-label"><span>Select Course *</span></label>
                                <select name="children[0][course_id]" class="field-select" required>
                                    <?php foreach (($coursesMap ?? []) as $cid => $cInfo): ?>
                                        <option value="<?= (int) $cid ?>"><?= esc($cInfo['breadcrumb_path']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Working Bottom `➕ Add Another Child` Button -->
                <div style="margin-bottom: 1.15rem;">
                    <button type="button" class="btn-secondary btn-sm js-onb-add-child-btn">➕ Add Another Child</button>
                </div>

                <!-- ONLY Preferred Timing in AM/PM (No Date Input Asked from Parent!) -->
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

                <button type="submit" class="btn-primary btn-full js-onb-submit-btn" style="justify-content:center; padding:0.9rem;">
                    🎯 Apply for Free Demo Class →
                </button>
            </form>
        </div>
    </div>
</section>
