<?php

/*
  Plainpad - Self Hosted Note Taking App

  Copyright (C) Alex Tselegidis - https://alextselegidis.com

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <https://www.gnu.org/licenses/>
*/

namespace Database\Seeders;

use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo data seeder for Plainpad.
 *
 * Generates a realistic set of notes that simulate the kind of content a
 * medical student would keep while studying. This seeder is intentionally
 * NOT registered in DatabaseSeeder.php and must be invoked manually:
 *
 *     php artisan db:seed --class=DemoSeeder
 *
 * The seeder is idempotent: re-running it will refresh the demo user's notes
 * rather than create duplicates.
 */
class DemoSeeder extends Seeder
{
    /**
     * Demo users that should receive the curated note bank. The default admin
     * account is included so a fresh demo install has sample notes visible
     * immediately after the standard admin login. A second non-admin user is
     * also seeded to showcase multi-user behaviour.
     */
    private const DEMO_PASSWORD = '12345678';

    private const DEMO_USERS = [
        [
            'name' => 'Admin',
            'email' => 'admin@example.org',
            'admin' => true,
        ],
        [
            'name' => 'Med Student',
            'email' => 'med.student@example.org',
            'admin' => false,
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = FakerFactory::create();

        // 1. Build the curated note bank once - both demo users receive the
        //    same set so the admin login showcases the full feature set.
        $notes = $this->buildNoteBank();

        foreach (self::DEMO_USERS as $demoUser) {
            // 2. Resolve (or create) the demo user that owns the notes. The
            //    Note model encrypts attributes via mutators that depend on
            //    Auth::user()->encrypt, so we keep encryption disabled here
            //    and write rows through the query builder to bypass the
            //    mutators safely during seeding.
            $userId = $this->resolveDemoUserId($demoUser);

            // 3. Wipe any prior demo notes for this user so the seeder can be
            //    run repeatedly without accumulating duplicates.
            DB::table('notes')->where('user_id', $userId)->delete();

            // 4. Pick a small subset of notes to flag as pinned, mimicking how
            //    a real user marks high-priority items. Pinned indexes are
            //    randomised per user so the two accounts do not look
            //    identical.
            $pinnedIndexes = array_flip((array) array_rand($notes, 12));

            // 5. Map note definitions onto database rows, generating a UUID
            //    and spreading creation timestamps across the past few months
            //    so the note list looks naturally aged.
            $rows = [];

            foreach ($notes as $index => $note) {
                $createdAt = $faker->dateTimeBetween('-6 months', 'now');
                $updatedAt = $faker->dateTimeBetween($createdAt, 'now');

                $rows[] = [
                    'id' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'title' => $note['title'],
                    'content' => $note['content'],
                    'pinned' => isset($pinnedIndexes[$index]),
                    'created_at' => $createdAt->format('Y-m-d H:i:s'),
                    'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
                ];
            }

            // 6. Insert in chunks to stay within database packet limits on
            //    both SQLite and MySQL backends.
            foreach (array_chunk($rows, 25) as $chunk) {
                DB::table('notes')->insert($chunk);
            }

            echo sprintf(
                'Seeded %d demo notes for %s (password: %s).%s',
                count($rows),
                $demoUser['email'],
                self::DEMO_PASSWORD,
                PHP_EOL
            );
        }
    }

    /**
     * Find or create a demo user used to own the seeded notes.
     *
     * Encryption is disabled for these users so the notes table stores
     * plain-text content that is readable directly in the UI without an
     * application key reset breaking the demo. If the user already exists
     * (e.g. the admin created by UsersSeeder) it is reused unchanged.
     *
     * @param array{name: string, email: string, admin: bool} $demoUser
     * @return string UUID of the demo user.
     */
    private function resolveDemoUserId(array $demoUser): string
    {
        $existing = DB::table('users')->where('email', $demoUser['email'])->first();

        if ($existing) {
            return $existing->id;
        }

        $userId = (string) Str::uuid();

        DB::table('users')->insert([
            'id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'name' => $demoUser['name'],
            'email' => $demoUser['email'],
            'password' => Hash::make(self::DEMO_PASSWORD),
            'locale' => 'en-US',
            'encrypt' => false,
            'admin' => $demoUser['admin'],
        ]);

        return $userId;
    }

    /**
     * Curated bank of 100 medical-student notes.
     *
     * Titles are unique and content is short, plain-text and reflective of
     * how a student might capture lecture takeaways, mnemonics, drug facts,
     * differentials and ward reminders.
     *
     * @return array<int, array{title: string, content: string}>
     */
    private function buildNoteBank(): array
    {
        return [
            ['title' => 'Cardiac cycle phases', 'content' => "The cardiac cycle alternates between systole and diastole, with each beat following a fixed sequence of pressure and volume changes.\n\n- Isovolumetric contraction: ventricles squeeze with all valves closed\n- Ejection: semilunar valves open, blood leaves the ventricles\n- Isovolumetric relaxation: pressure falls with all valves closed\n- Filling: AV valves open, ventricles refill\n\nRemember S1 = AV valves closing and S2 = semilunar valves closing; at rest diastole is roughly twice as long as systole."],
            ['title' => 'ECG lead placement', 'content' => "Correct precordial lead placement is essential before reading any 12-lead ECG, as misplacement mimics ischemia or hypertrophy.\n\n- V1: 4th intercostal space, right sternal border\n- V2: 4th intercostal space, left sternal border\n- V3: midway between V2 and V4\n- V4: 5th intercostal space, midclavicular line\n- V5: anterior axillary line, same level as V4\n- V6: midaxillary line, same level as V4\n\nIf the axis or R-wave progression looks odd, recheck lead positions before chasing pathology."],
            ['title' => 'Murmur differential', 'content' => "Timing the murmur in the cardiac cycle and noting where it radiates narrows the differential dramatically.\n\n- Aortic stenosis: systolic crescendo-decrescendo, radiates to carotids\n- Mitral regurgitation: holosystolic, radiates to axilla\n- Aortic regurgitation: early diastolic decrescendo at left sternal border\n- Mitral stenosis: opening snap followed by mid-diastolic rumble at apex\n\nAlways correlate auscultation with the patient's pulse and JVP before ordering an echo."],
            ['title' => 'STEMI management checklist', 'content' => "STEMI is a time-critical diagnosis where every minute of delay costs myocardium, so use a checklist to avoid omissions.\n\n- Morphine for refractory pain\n- Oxygen if SpO2 < 90%\n- Nitrates unless inferior MI with RV involvement or hypotension\n- Aspirin 325 mg chewed\n- Beta blocker if no contraindication\n- P2Y12 inhibitor and heparin before cath\n\nAim for door-to-balloon time under 90 minutes and call the cath lab before completing the workup."],
            ['title' => 'Heart failure NYHA classes', 'content' => "NYHA functional classification grades how much heart failure limits a patient's daily activity and drives therapy escalation.\n\n- Class I: no symptoms with ordinary activity\n- Class II: slight limitation with ordinary activity\n- Class III: marked limitation, comfortable only at rest\n- Class IV: symptoms even at rest\n\nDocument NYHA class at every visit so changes in diuretic dose and GDMT escalation can be justified."],
            ['title' => 'GDMT for HFrEF', 'content' => "Guideline-directed medical therapy for HFrEF rests on four drug pillars that should each be started early and titrated together.\n\n- ARNI (or ACEi/ARB if ARNI not tolerated)\n- Beta blocker: carvedilol, metoprolol succinate, or bisoprolol\n- Mineralocorticoid receptor antagonist such as spironolactone\n- SGLT2 inhibitor regardless of diabetes status\n\nTitrate every two weeks as blood pressure, potassium, and renal function allow rather than waiting for full doses serially."],
            ['title' => 'Atrial fibrillation rate vs rhythm', 'content' => "AF management requires balancing rate vs rhythm control with a separate decision about stroke prevention.\n\n- Rate control first line: metoprolol or diltiazem\n- Rhythm control if symptomatic despite rate control\n- Rhythm control preferred for new-onset AF in younger patients\n- Anticoagulation guided by CHA2DS2-VASc, not by rhythm strategy\n\nRemember that successful rhythm control does not remove the need for anticoagulation in patients with elevated stroke risk."],
            ['title' => 'CHA2DS2-VASc scoring', 'content' => "CHA2DS2-VASc estimates annual stroke risk in non-valvular AF and guides anticoagulation decisions.\n\n- C: congestive heart failure (1)\n- H: hypertension (1)\n- A2: age >=75 (2)\n- D: diabetes (1)\n- S2: prior stroke or TIA (2)\n- V: vascular disease (1)\n- A: age 65-74 (1)\n- Sc: female sex (1)\n\nAnticoagulate when the score is >=2 in men or >=3 in women, and reassess yearly because the score is age-dependent."],
            ['title' => 'Hypertension stages JNC8', 'content' => "Stage your patient's blood pressure on the average of multiple readings, not a single in-office value.\n\n- Normal: <120/80\n- Elevated: 120-129/<80\n- Stage 1: 130-139/80-89\n- Stage 2: >=140/90\n\nStart with two agents from different classes when the BP is more than 20/10 above goal."],
            ['title' => 'First-line antihypertensives', 'content' => "Initial antihypertensive choice is driven by demographics and comorbidities rather than personal preference.\n\n- Non-black: thiazide, ACEi, ARB, or CCB\n- Black: thiazide or CCB\n- CKD: ACEi or ARB for renoprotection\n- Diabetes with albuminuria: ACEi or ARB\n\nNever combine an ACEi with an ARB because of the risk of hyperkalemia and acute kidney injury."],
            ['title' => 'Endocarditis Duke criteria', 'content' => "Definite endocarditis requires either pathology or 2 major, 1 major + 3 minor, or 5 minor Duke criteria.\n\n- Major: typical organisms in two separate blood cultures\n- Major: echocardiographic evidence of endocardial involvement\n- Minor: predisposing condition or IVDU\n- Minor: fever >=38 degrees\n- Minor: vascular phenomena (Janeway, septic emboli)\n- Minor: immunologic phenomena (Osler, Roth, glomerulonephritis)\n- Minor: microbiology not meeting major criteria\n\nObtain three blood culture sets from separate sites before starting antibiotics whenever the patient is stable enough to wait."],
            ['title' => 'DVT Wells score basics', 'content' => "The Wells score for DVT stratifies pretest probability so the right next test is chosen.\n\n- Active cancer\n- Paralysis or recent immobilization\n- Bedridden >3 days or major surgery within 12 weeks\n- Tenderness along deep venous system\n- Entire leg swollen\n- Calf swelling >3 cm vs unaffected side\n- Pitting edema confined to symptomatic leg\n- Collateral superficial veins\n\nScore >=3 is high probability and warrants ultrasound; <2 with a negative D-dimer effectively rules out DVT."],

            ['title' => 'Asthma vs COPD spirometry', 'content' => "Spirometry distinguishes asthma from COPD by reversibility rather than by FEV1/FVC alone.\n\n- Both diseases: FEV1/FVC < 0.7\n- Asthma: post-bronchodilator improvement >=12% and 200 mL\n- COPD: persistent obstruction after bronchodilator\n- DLCO low in COPD with emphysema\n- DLCO normal or high in asthma\n\nWhen spirometry is borderline, methacholine challenge or a 4-week ICS trial helps separate the two."],
            ['title' => 'COPD GOLD groups', 'content' => "GOLD groups combine symptoms (mMRC/CAT) with exacerbation history to choose initial inhaler therapy.\n\n- Group A: low symptoms, low risk -> SABA prn\n- Group B: high symptoms, low risk -> LABA or LAMA\n- Group E (formerly C/D): >=2 exacerbations or hospitalization -> LABA + LAMA\n- Add ICS to LABA + LAMA if blood eosinophils >300\n\nReassess at every visit because exacerbations move patients between groups and prompt step-up therapy."],
            ['title' => 'Asthma stepwise therapy', 'content' => "Asthma therapy follows a stepwise model that should be reviewed at every visit for control.\n\n- Step 1-2: SABA prn or low-dose ICS-formoterol\n- Step 3: low-dose ICS + LABA\n- Step 4: medium-dose ICS + LABA\n- Step 5: add LAMA, plus biologic if eosinophilic or allergic\n\nStep up when control is poor and step down after 3 months of stability to find the lowest effective dose."],
            ['title' => 'Pneumonia CURB-65', 'content' => "CURB-65 helps decide where to treat community-acquired pneumonia.\n\n- C: confusion\n- U: urea >7 mmol/L\n- R: respiratory rate >=30\n- B: BP <90/60\n- 65: age >=65\n\nScores 0-1 outpatient, 2 ward, >=3 ICU; empiric antibiotics are amox or doxy outpatient and ceftriaxone + azithromycin inpatient."],
            ['title' => 'Pulmonary embolism workup', 'content' => "PE workup is driven by pretest probability, not by D-dimer in everyone.\n\n- Low Wells: PERC or D-dimer to rule out\n- Moderate or high Wells: CT pulmonary angiogram\n- Massive PE with shock: systemic tPA\n- Sub-massive with RV strain: catheter-directed thrombolysis\n\nDo not order a D-dimer in high-pretest-probability patients because a negative result will not change management."],
            ['title' => 'Pleural effusion Light criteria', 'content' => "Light criteria classify a pleural effusion as exudative if any one criterion is met.\n\n- Pleural protein/serum protein >0.5\n- Pleural LDH/serum LDH >0.6\n- Pleural LDH > 2/3 the upper limit of normal serum LDH\n\nAn effusion not meeting any of these is a transudate, typically driven by CHF or cirrhosis."],
            ['title' => 'ARDS Berlin definition', 'content' => "The Berlin definition standardises ARDS diagnosis and grades severity by the PaO2/FiO2 ratio on PEEP >=5.\n\n- Acute onset within 1 week of insult\n- Bilateral opacities not explained by effusions or atelectasis\n- Not fully explained by cardiac failure or fluid overload\n- Mild: PaO2/FiO2 200-300\n- Moderate: PaO2/FiO2 100-200\n- Severe: PaO2/FiO2 <=100\n\nLow tidal volume ventilation (6 mL/kg ideal body weight) is the cornerstone of management."],
            ['title' => 'TB screening reminders', 'content' => "PPD induration thresholds depend on the patient's risk category, not just absolute size.\n\n- >=5 mm: HIV, recent TB contact, transplant or other immunosuppression\n- >=10 mm: immigrants from high-prevalence areas, IVDU, healthcare workers\n- >=15 mm: low-risk individuals\n\nIGRA is preferred over PPD in BCG-vaccinated patients to avoid false positives."],

            ['title' => 'GI bleed initial steps', 'content' => "Resuscitation comes before localisation in any significant GI bleed.\n\n- Two large-bore IVs\n- Type and crossmatch\n- IV PPI for upper bleed\n- Keep NPO\n- Transfuse to Hb 7 (9 if cardiac comorbidity)\n\nUpper bleed needs EGD within 24 hours; lower bleed needs colonoscopy after adequate prep."],
            ['title' => 'H. pylori triple therapy', 'content' => "Standard H. pylori eradication uses triple therapy unless local clarithromycin resistance is high.\n\n- PPI BID\n- Clarithromycin 500 mg BID\n- Amoxicillin 1 g BID for 14 days\n\nWhen macrolide resistance is high, switch to bismuth quadruple: PPI + bismuth + tetracycline + metronidazole."],
            ['title' => 'IBD UC vs Crohn comparison', 'content' => "UC and Crohn share features but differ in distribution, depth, and complications.\n\n- UC: continuous involvement starting at the rectum\n- UC: mucosal disease with bloody diarrhea, ANCA positive\n- Crohn: skip lesions from mouth to anus\n- Crohn: transmural disease with fistulas, ASCA positive, granulomas\n\nSmoking helps UC but worsens Crohn, which still surprises patients during counseling."],
            ['title' => 'Acute pancreatitis criteria', 'content' => "Two of three Atlanta criteria establish the diagnosis of acute pancreatitis.\n\n- Typical epigastric pain radiating to the back\n- Lipase >3x upper limit of normal\n- Imaging findings consistent with pancreatitis\n\nGallstones are the leading cause; also remember alcohol, hypertriglyceridemia, post-ERCP, and drug-induced cases."],
            ['title' => 'Liver enzyme patterns', 'content' => "Pattern recognition narrows the cause of abnormal LFTs before any imaging.\n\n- AST > ALT 2:1 -> alcoholic hepatitis\n- ALT > AST -> viral hepatitis or NAFLD\n- ALP and GGT up with bilirubin -> cholestatic\n- AST/ALT > ALP -> hepatocellular\n\nIsolated ALP elevation should prompt fractionation to confirm a hepatic source before further workup."],
            ['title' => 'Cirrhosis decompensation signs', 'content' => "Decompensation in cirrhosis manifests as one of four cardinal events that all imply poor prognosis.\n\n- Ascites\n- Variceal bleeding\n- Hepatic encephalopathy\n- Jaundice\n\nManage with salt restriction, spironolactone + furosemide, lactulose for encephalopathy, beta blockers for varices, and paracentesis when ascites is tense."],
            ['title' => 'Diverticulitis vs diverticulosis', 'content' => "Diverticulosis is structural; diverticulitis is the acute inflammatory complication.\n\n- Diverticulosis: outpouchings, often asymptomatic, can cause painless bleeding\n- Diverticulitis: LLQ pain, fever, leukocytosis\n- CT confirms and grades severity\n\nUncomplicated diverticulitis often resolves with observation alone; antibiotics are reserved for complicated or systemic disease."],
            ['title' => 'Appendicitis exam pearls', 'content' => "Classic exam findings still anchor the diagnosis even when imaging is available.\n\n- McBurney point tenderness\n- Rovsing sign\n- Psoas sign\n- Obturator sign\n- WBC and CRP up\n\nUse ultrasound first in children and pregnant patients and CT in other adults; manage with appendectomy or selective antibiotic-only therapy."],

            ['title' => 'Diabetes diagnostic criteria', 'content' => "Any one of four ADA criteria establishes diabetes, but a second confirmatory test is usually needed.\n\n- A1c >=6.5%\n- Fasting plasma glucose >=126 mg/dL\n- 2-hour OGTT >=200 mg/dL\n- Random glucose >=200 mg/dL with classic symptoms\n\nUnequivocal hyperglycemia with symptoms can be diagnosed on a single value without a confirmatory repeat."],
            ['title' => 'DKA management', 'content' => "DKA management hinges on simultaneously correcting fluid loss, the anion gap, and the precipitant.\n\n- IV fluids: NS bolus then transition to 1/2 NS\n- Insulin drip 0.1 u/kg/hr after potassium replaced if K <5.3\n- Add dextrose when glucose drops below 250\n- Treat the precipitant (infection, MI, missed insulin)\n\nAvoid bicarbonate unless the pH is below 6.9 because it can worsen intracellular acidosis."],
            ['title' => 'HHS vs DKA', 'content' => "HHS and DKA share hyperglycemia but differ across several key axes.\n\n- DKA: type 1, glucose 300-500, anion gap, ketones present\n- DKA: hours to days onset\n- HHS: type 2, glucose >600, minimal ketones\n- HHS: profound dehydration, mental status changes, days to weeks onset\n\nFluid deficits in HHS are typically larger than in DKA, so resuscitate slowly to avoid cerebral edema."],
            ['title' => 'Thyroid storm treatment', 'content' => "Thyroid storm is a clinical diagnosis that requires layered therapy targeting different steps in the hormone pathway.\n\n- Beta blocker (propranolol) for symptoms\n- PTU first to block hormone synthesis\n- Iodine 1 hour after PTU to block release\n- Hydrocortisone to block T4 -> T3 conversion\n- Cooling and treat trigger\n\nPTU is preferred over methimazole acutely because it blocks peripheral conversion of T4 to T3."],
            ['title' => 'Adrenal insufficiency', 'content' => "Distinguishing primary from secondary adrenal insufficiency drives both diagnosis and lifelong therapy.\n\n- Primary (Addison): low cortisol, high ACTH, hyperpigmentation, hyperkalemia\n- Secondary: low ACTH, no hyperpigmentation, normal potassium\n- ACTH stimulation test confirms when cortisol fails to rise\n\nTreat primary disease with hydrocortisone plus fludrocortisone; secondary needs only glucocorticoid replacement."],
            ['title' => 'Cushing workup', 'content' => "Cushing syndrome workup proceeds in two stages: confirm hypercortisolism, then localize the source.\n\n- 24-hour urinary free cortisol\n- Late-night salivary cortisol\n- 1 mg overnight dexamethasone suppression test\n- Then check ACTH: low -> adrenal source; high -> pituitary vs ectopic\n- Differentiate with high-dose dex suppression and CRH testing\n\nPseudo-Cushing from alcohol, depression, or obesity is a common pitfall, so confirm two screening tests before imaging."],
            ['title' => 'Hypothyroid vs hyperthyroid', 'content' => "Many symptoms of thyroid dysfunction are mirror images of each other.\n\n- Hypo: cold intolerance, weight gain, constipation, bradycardia, dry skin, high TSH\n- Hyper: heat intolerance, weight loss, diarrhea, tachycardia, tremor, low TSH\n\nA single TSH is the best screening test, but free T4 and T3 are needed when TSH is suppressed or pituitary disease is suspected."],
            ['title' => 'Hyponatremia algorithm', 'content' => "A stepwise approach to hyponatremia avoids the trap of treating the number without the cause.\n\n- Confirm low serum osmolality = true hypotonic hyponatremia\n- Hypovolemic: UNa <20 extrarenal loss, >20 renal loss\n- Euvolemic: SIADH or hypothyroidism\n- Hypervolemic: CHF, cirrhosis, nephrotic syndrome\n\nCorrect chronic hyponatremia by no more than 8 mmol/L per 24 hours to avoid osmotic demyelination."],

            ['title' => 'AKI staging KDIGO', 'content' => "KDIGO stages AKI by the change in creatinine or urine output, whichever is worse.\n\n- Stage 1: Cr 1.5-1.9x baseline or +0.3 mg/dL; UO <0.5 mL/kg/h x 6-12h\n- Stage 2: Cr 2-2.9x baseline\n- Stage 3: Cr >=3x baseline, Cr >=4 mg/dL, or RRT initiated\n\nFollow the trend rather than a single creatinine value because muscle mass and timing both bias absolute numbers."],
            ['title' => 'CKD dietary points', 'content' => "Diet in CKD is about controlling several solutes simultaneously, not just protein.\n\n- Low sodium\n- Controlled potassium\n- Low phosphate\n- Moderate protein 0.6-0.8 g/kg unless on dialysis\n\nAvoid NSAIDs, IV contrast, and aminoglycosides whenever there is a safer alternative."],
            ['title' => 'Nephrotic vs nephritic', 'content' => "Two glomerular syndromes share lab abnormalities but tell very different stories.\n\n- Nephrotic: proteinuria >3.5 g, edema, hypoalbuminemia, hyperlipidemia\n- Nephritic: hematuria, RBC casts, hypertension, mild proteinuria, oliguria\n\nA mixed picture (e.g., MPGN, lupus nephritis) deserves prompt biopsy because therapy depends on histology."],
            ['title' => 'Acid-base step-by-step', 'content' => "A reliable acid-base interpretation always follows the same five-step sequence.\n\n- Look at the pH\n- Identify the primary disorder\n- Calculate expected compensation\n- Compute the anion gap\n- Apply delta gap if there is an anion-gap acidosis\n\nWinter formula is essential: expected PaCO2 = 1.5(HCO3) + 8 +/- 2 in metabolic acidosis."],
            ['title' => 'Hyperkalemia ECG and tx', 'content' => "Hyperkalemia management protects the membrane first, shifts potassium into cells, then removes it from the body.\n\n- ECG progression: peaked T -> wide QRS -> sine wave\n- Calcium gluconate to stabilise membrane\n- Insulin + D50 to shift\n- Albuterol to shift\n- Bicarbonate if acidotic\n- Remove with kayexalate/lokelma, furosemide, or dialysis\n\nAlways recheck a potassium drawn from a hemolyzed sample before treating an asymptomatic patient with a normal ECG."],
            ['title' => 'Renal stone composition', 'content' => "Stone composition predicts both treatment and prevention strategies.\n\n- Calcium oxalate: most common overall\n- Uric acid: radiolucent, forms in acid urine\n- Struvite: staghorn calculi, associated with Proteus UTIs\n- Cystine: rare, kids with cystinuria\n\nHydrate, strain the urine, and control pain; involve urology when stones are >5 mm or causing obstruction."],

            ['title' => 'Stroke initial workup', 'content' => "Acute stroke care is dominated by establishing the time of last known well and excluding hemorrhage.\n\n- Time last known well\n- Glucose and NIHSS at the bedside\n- Non-contrast CT to exclude bleed\n- tPA within 4.5 hours if no contraindications\n- Mechanical thrombectomy within 24 hours for LVO with favorable imaging\n\nDo not delay neuroimaging for routine labs once airway and glucose are addressed."],
            ['title' => 'Headache red flags', 'content' => "Most headaches are benign, but a short list of red flags should prompt urgent imaging.\n\n- Sudden thunderclap onset\n- Worst headache of life\n- Fever with neck stiffness\n- Focal neurologic deficits\n- New onset after age 50\n- Immunocompromised patient\n- Triggered by posture or cough\n- Papilledema on exam\n\nWhen any red flag is present, image first and consider lumbar puncture if the CT is negative."],
            ['title' => 'Seizure first-line drugs', 'content' => "Choosing the first AED depends on seizure type, sex, and pregnancy status.\n\n- Focal: lamotrigine or levetiracetam\n- Generalized tonic-clonic: valproate (avoid in pregnancy) or levetiracetam\n- Absence: ethosuximide or valproate\n- Status epilepticus: lorazepam IV then loading AED\n\nReview drug interactions and contraception any time an enzyme-inducing AED is started in a person of childbearing potential."],
            ['title' => 'MS vs ALS quick contrast', 'content' => "MS and ALS both produce upper motor neuron findings but otherwise differ profoundly.\n\n- MS: relapsing-remitting course\n- MS: UMN signs plus sensory and optic neuritis\n- MS: MRI plaques, CSF oligoclonal bands\n- ALS: pure motor disease\n- ALS: UMN and LMN signs without sensory loss\n- ALS: EMG fasciculations, fatal within years\n\nWhen the picture is mixed, refer early to a neuromuscular specialist before disease-modifying decisions are made."],
            ['title' => 'Parkinson cardinal signs', 'content' => "TRAP captures the four cardinal motor features of Parkinson disease.\n\n- T: resting pill-rolling tremor\n- R: cogwheel rigidity\n- A: akinesia or bradykinesia\n- P: postural instability\n\nLevodopa-carbidopa is the symptomatic gold standard, while MAO-B inhibitors and dopamine agonists are useful in younger patients."],
            ['title' => 'Meningitis empiric coverage', 'content' => "Empiric coverage in suspected bacterial meningitis depends on age and immune status.\n\n- Adults <50: ceftriaxone + vancomycin + dexamethasone\n- Adults >50 or immunocompromised: add ampicillin for Listeria\n- Neonates: ampicillin + cefotaxime or gentamicin\n\nDo not delay antibiotics for LP or CT in the unstable patient: draw cultures and treat first."],
            ['title' => 'Glasgow Coma Scale recap', 'content' => "GCS scores three domains and is best used as a trend rather than a snapshot.\n\n- Eye opening: 1-4\n- Verbal response: 1-5\n- Motor response: 1-6\n\nIntubate when the score falls to 8 or below, and document each component so subsequent providers can detect deterioration."],
            ['title' => 'Spinal cord syndromes', 'content' => "Pattern recognition of spinal cord syndromes localises the lesion before imaging confirms it.\n\n- Anterior cord: motor and pain/temp loss with vibration spared\n- Brown-Sequard: ipsilateral motor and vibration loss, contralateral pain/temp loss\n- Central cord: upper extremity weakness > lower\n- Cauda equina: saddle anesthesia and urinary retention\n\nCauda equina is a surgical emergency: arrange MRI and a spine consult immediately."],

            ['title' => 'Sepsis bundle 1-hour', 'content' => "The Surviving Sepsis 1-hour bundle bundles together the highest-yield early actions.\n\n- Measure lactate\n- Draw blood cultures before antibiotics\n- Start broad-spectrum antibiotics\n- 30 mL/kg crystalloid for hypotension or lactate >=4\n- Vasopressors (norepinephrine first) to maintain MAP >=65\n\nReassess fluid responsiveness rather than continuing automatic boluses to avoid pulmonary edema."],
            ['title' => 'CAP outpatient antibiotics', 'content' => "Outpatient CAP antibiotic choice depends on comorbidities and recent antibiotic exposure.\n\n- Healthy adult: amoxicillin or doxycycline\n- Comorbid (CHF, COPD, DM, cancer): amox-clav or cephalosporin + macrolide\n- Alternative: respiratory fluoroquinolone\n\nA 5-day course is usually sufficient when the patient is afebrile for 48-72 hours and clinically stable."],
            ['title' => 'UTI uncomplicated tx', 'content' => "Uncomplicated cystitis is treated with short, narrow regimens to limit resistance.\n\n- Nitrofurantoin 100 mg BID x 5 days\n- TMP-SMX DS BID x 3 days\n- Fosfomycin 3 g x 1 dose\n\nAvoid fluoroquinolones first line; pyelonephritis needs IV ceftriaxone followed by an oral step-down."],
            ['title' => 'HIV initial workup', 'content' => "A new HIV diagnosis launches a structured baseline workup before therapy is started.\n\n- HIV-1/2 antibody and antigen with confirmatory RNA viral load\n- CD4 count and genotype resistance\n- Hepatitis A, B, and C serologies\n- Latent TB screen\n- STI panel\n- Baseline CMP, CBC, fasting lipids\n\nCounsel about confidentiality, partner notification, and ART adherence at the very first visit."],
            ['title' => 'Antibiotic spectrum cheat', 'content' => "A few mental anchors cover most empiric antibiotic choices.\n\n- Vancomycin: gram-positive including MRSA\n- Cefepime or pip-tazo: pseudomonas plus broad gram-negative\n- Metronidazole or clindamycin: anaerobes\n- Aztreonam: gram-negative only, safe in PCN allergy\n\nAlways de-escalate as soon as cultures and susceptibilities return."],
            ['title' => 'TB regimen RIPE', 'content' => "RIPE therapy combines four agents for two months followed by two for an additional four.\n\n- Rifampin: orange secretions, induces CYP enzymes\n- Isoniazid: give B6 to prevent neuropathy\n- Pyrazinamide: raises uric acid\n- Ethambutol: optic neuritis risk\n\nBaseline LFTs and visual acuity are mandatory before starting therapy."],
            ['title' => 'Cellulitis vs erysipelas', 'content' => "Cellulitis and erysipelas differ in depth, demarcation, and most likely organism.\n\n- Cellulitis: deeper, ill-defined borders, often staph or strep\n- Erysipelas: superficial, well-demarcated, classic Strep pyogenes\n\nTreat with cephalexin or dicloxacillin for non-purulent disease and cover MRSA when there is purulent drainage or abscess."],
            ['title' => 'Endocarditis empiric', 'content' => "Empiric endocarditis therapy depends on valve type and acuity while cultures are pending.\n\n- Native valve subacute: vancomycin + ceftriaxone\n- Native valve acute: vancomycin + cefepime\n- Prosthetic valve: vancomycin + gentamicin + rifampin\n\nSurgery is indicated for heart failure, abscess, recurrent emboli, or fungal endocarditis."],

            ['title' => 'Beta blocker pearls', 'content' => "Beta blockers vary in selectivity and ancillary properties, which guides choice in specific patients.\n\n- Cardioselective (beta-1): metoprolol, atenolol, bisoprolol\n- Non-selective: propranolol (good for migraine and thyroid storm)\n- Combined alpha/beta: carvedilol, labetalol\n\nNever stop beta blockers abruptly because rebound tachycardia and angina are real risks."],
            ['title' => 'Anticoagulant reversal', 'content' => "Each anticoagulant has its own reversal agent, and 4F-PCC is the universal backup.\n\n- Warfarin: vitamin K plus 4F-PCC for major bleeding\n- Heparin: protamine\n- Factor Xa DOACs: andexanet alfa\n- Dabigatran: idarucizumab\n\nWhen a specific reversal agent is unavailable, give 4F-PCC and supportive care while monitoring for thrombotic complications."],
            ['title' => 'NSAID adverse effects', 'content' => "NSAIDs cause more harm than they get credit for outside of the acute setting.\n\n- GI ulcers and bleeding\n- Acute kidney injury\n- Hyperkalemia\n- Worsening hypertension\n- Sodium and fluid retention\n\nAvoid in CKD, CHF, and active peptic ulcer disease, and add a PPI if long-term NSAID use is unavoidable."],
            ['title' => 'Steroid taper guidance', 'content' => "Long courses of glucocorticoids require deliberate tapering to avoid adrenal insufficiency.\n\n- Taper if prednisone >20 mg/day or duration >3 weeks\n- Stress-dose hydrocortisone for major surgery or sepsis\n- Watch for hyperglycemia, mood changes, osteoporosis\n\nReview bone health, vaccines, and PJP prophylaxis whenever a long steroid course is anticipated."],
            ['title' => 'QT prolonging drugs', 'content' => "Several common drugs prolong the QT and risk torsades, especially when combined.\n\n- Macrolides: azithromycin, clarithromycin\n- Fluoroquinolones\n- Ondansetron\n- Methadone\n- Quetiapine\n- Amiodarone, sotalol\n\nCheck a baseline QTc and correct hypokalemia or hypomagnesemia before stacking these drugs."],
            ['title' => 'Common drug interactions', 'content' => "A handful of drug pairings cause the majority of dangerous interactions seen on the wards.\n\n- Warfarin + amiodarone -> bleeding\n- SSRI + MAOI -> serotonin syndrome\n- Statin + macrolide -> rhabdomyolysis\n- Sildenafil + nitrate -> profound hypotension\n\nReview the medication list at every transition of care because new prescribers rarely see the full picture."],
            ['title' => 'Insulin types onset', 'content' => "Knowing onset and duration of common insulins is essential for safe titration.\n\n- Lispro/aspart: onset 15 min, duration 4-5 hr\n- Regular: onset 30 min, duration 6-8 hr\n- NPH: onset 2 hr, duration 12-18 hr\n- Glargine/detemir: onset ~2 hr, peakless 24 hr\n- Degludec: onset 1-2 hr, duration 24-42 hr\n\nMatch the prandial insulin onset to the meal pattern and use long-acting basal as a steady background."],
            ['title' => 'Pain ladder WHO', 'content' => "The WHO analgesic ladder steps therapy with severity while emphasising adjuvants.\n\n- Step 1: non-opioid +/- adjuvant\n- Step 2: weak opioid (codeine, tramadol)\n- Step 3: strong opioid (morphine, oxycodone)\n\nAlways start a bowel regimen at the same time you start any opioid to prevent constipation."],

            ['title' => 'Anemia workup MCV', 'content' => "MCV is the first branch point in any anemia workup.\n\n- Microcytic: iron deficiency, thalassemia, anemia of chronic disease, sideroblastic, lead\n- Normocytic: hemolysis, kidney disease, marrow failure\n- Macrocytic: B12 or folate deficiency, alcohol, hypothyroidism, MDS\n\nAlways check a reticulocyte count to distinguish a hypoproductive from a destructive process."],
            ['title' => 'Iron studies pattern', 'content' => "Iron studies separate the common microcytic causes when MCV alone is ambiguous.\n\n- Iron deficiency: low ferritin, high TIBC, low transferrin saturation\n- Anemia of chronic disease: normal-high ferritin, low TIBC, low saturation\n- Thalassemia: normal iron panel\n\nFerritin is also an acute phase reactant, so combine it with CRP when the picture is unclear."],
            ['title' => 'Coagulation PT vs PTT', 'content' => "PT and PTT each track different arms of the coagulation cascade.\n\n- PT/INR: extrinsic pathway (factor VII), warfarin, liver disease\n- PTT: intrinsic pathway (factors VIII, IX, XI), heparin, hemophilia\n- Both prolonged: common pathway, DIC, severe liver disease\n\nA mixing study clarifies whether prolongation is due to factor deficiency or a circulating inhibitor."],
            ['title' => 'TTP pentad', 'content' => "TTP is suggested whenever a microangiopathic hemolytic anemia and thrombocytopenia coexist.\n\n- F: fever\n- A: microangiopathic anemia\n- T: thrombocytopenia\n- R: renal involvement\n- N: neurologic changes\n\nStart plasma exchange immediately and avoid platelet transfusions, which can worsen microthrombi."],
            ['title' => 'Leukemia quick differentiation', 'content' => "Age, blast morphology, and cytogenetics quickly narrow the leukemia category.\n\n- ALL: kids, lymphoblasts, TdT positive\n- AML: adults, Auer rods, M3 = APML with t(15;17)\n- CLL: elderly, smudge cells\n- CML: t(9;22) BCR-ABL, basophilia\n\nAPML is a hematologic emergency because of DIC, so start ATRA empirically when suspected."],
            ['title' => 'Tumor lysis syndrome', 'content' => "Tumor lysis is a metabolic emergency in highly proliferative cancers undergoing therapy.\n\n- High potassium\n- High phosphate\n- High uric acid\n- Low calcium\n- Acute kidney injury\n\nPrevent with aggressive hydration plus allopurinol, escalating to rasburicase in high-risk hematologic malignancies."],

            ['title' => 'Pregnancy lab changes', 'content' => "Pregnancy shifts many lab values in predictable directions that should not be misread as disease.\n\n- Increased: cardiac output, GFR, WBC, alkaline phosphatase, fibrinogen\n- Decreased: hemoglobin (dilutional), BUN/creatinine, PaCO2 (respiratory alkalosis), albumin\n\nAlways compare to pregnancy-specific reference ranges before flagging an abnormality."],
            ['title' => 'Preeclampsia criteria', 'content' => "Preeclampsia is diagnosed after 20 weeks of gestation with hypertension plus proteinuria or end-organ damage.\n\n- BP >=140/90 plus proteinuria\n- Or BP >=140/90 with any end-organ feature\n- Severe: BP >=160/110\n- Severe: AST/ALT 2x baseline\n- Severe: low platelets\n- Severe: pulmonary edema\n- Severe: new neurologic symptoms\n\nMagnesium sulfate prevents seizures and definitive treatment is delivery once severe features develop."],
            ['title' => 'Apgar score', 'content' => "Apgar scores newborn transition at 1 and 5 minutes across five domains.\n\n- Heart rate\n- Respiratory effort\n- Muscle tone\n- Reflex irritability\n- Color\n\nThe score helps identify the need for resuscitation but does not predict long-term neurologic outcome."],
            ['title' => 'Pediatric vital ranges', 'content' => "Pediatric vital signs vary widely by age, so memorise approximate ranges before night shifts.\n\n- Newborn: HR 100-160, RR 30-60\n- Toddler: HR 90-150, RR 24-40\n- School age: HR 70-110, RR 18-30\n\nA quick estimate for systolic BP in children is 70 + (2 x age in years)."],
            ['title' => 'Vaccine schedule highlights', 'content' => "A few major milestones cover most pediatric immunizations.\n\n- Birth: HepB\n- 2/4/6 months: DTaP, Hib, IPV, PCV, RV\n- 12-15 months: MMR, varicella, HepA\n- 11-12 years: Tdap, HPV, MenACWY\n- Annual influenza from 6 months\n\nCheck the catch-up schedule whenever a child presents with an incomplete record."],
            ['title' => 'Bilirubin in newborn', 'content' => "Newborn jaundice patterns help separate physiologic from pathologic causes.\n\n- Physiologic: starts day 2-3, peaks day 5, resolves in 1-2 weeks\n- Pathologic: appears <24 hr, rises rapidly, conjugated >2 mg/dL, persists >2 weeks\n\nUse the hour-specific nomogram to decide whether phototherapy is needed rather than relying on a single threshold."],

            ['title' => 'Depression PHQ-9', 'content' => "PHQ-9 quantifies depression severity and tracks treatment response.\n\n- 5-9: mild\n- 10-14: moderate\n- 15-19: moderately severe\n- 20+: severe\n\nTreat moderate or worse with an SSRI plus therapy and reassess at 4-6 weeks; never skip the suicide-risk question."],
            ['title' => 'Antidepressant choice', 'content' => "Antidepressant selection considers side-effect profile and comorbid symptoms, not efficacy alone.\n\n- First line: SSRI (sertraline, escitalopram)\n- Bupropion: low energy, want to avoid sexual side effects\n- Mirtazapine: insomnia and poor appetite\n- Avoid TCAs in elderly because of anticholinergic load\n\nCounsel that benefit takes 4-6 weeks and warn about early activation and suicidality in younger patients."],
            ['title' => 'Lithium toxicity', 'content' => "Lithium has a narrow therapeutic window and a recognisable toxicity syndrome.\n\n- Tremor\n- GI upset\n- Ataxia\n- Confusion\n- Seizure\n\nLevels >1.5 mEq/L are concerning and >2.5 are severe; risk factors include dehydration, NSAIDs, ACEi, and thiazides, with hemodialysis indicated for severe poisoning."],
            ['title' => 'Schizophrenia first-line', 'content' => "Second-generation antipsychotics balance efficacy with metabolic risk in schizophrenia.\n\n- Risperidone\n- Olanzapine\n- Aripiprazole\n\nMonitor weight, lipids, and glucose; reserve clozapine for treatment-resistant disease and follow ANC weekly because of agranulocytosis risk."],

            ['title' => 'Brachial plexus mnemonic', 'content' => "Brachial plexus organisation is most easily memorised with the classic mnemonic.\n\n- Roots\n- Trunks\n- Divisions\n- Cords\n- Branches\n\nRead That Damn Cadaver Book; roots span C5-T1 and cords are named for their position relative to the axillary artery."],
            ['title' => 'Coronary artery anatomy', 'content' => "Coronary anatomy explains why specific MIs cause specific complications.\n\n- LAD: anterior wall and septum\n- LCx: lateral wall\n- RCA: inferior wall, AV node in 90%, SA node in 60%\n\nAround 85% of patients are right-dominant, which is why inferior MIs frequently cause AV block."],
            ['title' => 'Cranial nerves quick recall', 'content' => "Use the classic order to keep the cranial nerves straight under pressure.\n\n- I olfactory\n- II optic\n- III oculomotor\n- IV trochlear\n- V trigeminal\n- VI abducens\n- VII facial\n- VIII vestibulocochlear\n- IX glossopharyngeal\n- X vagus\n- XI accessory\n- XII hypoglossal\n\nMatch each nerve to a focused exam maneuver during practice so the bedside test becomes automatic."],
            ['title' => 'Liver segments', 'content' => "The Couinaud system divides the liver into eight functionally independent segments.\n\n- Each segment has its own portal inflow and hepatic venous outflow\n- Segment IV separates the right and left hemilivers\n- Segments are numbered clockwise on a frontal view\n\nKnowing segments matters most for surgical planning and for localising lesions on cross-sectional imaging."],
            ['title' => 'Carpal bones order', 'content' => "Carpal bones are best memorised in the proximal-to-distal, lateral-to-medial order.\n\n- Scaphoid\n- Lunate\n- Triquetrum\n- Pisiform\n- Trapezium\n- Trapezoid\n- Capitate\n- Hamate\n\nSome Lovers Try Positions That They Cannot Handle; the scaphoid is the most fractured carpal and at high risk for avascular necrosis."],
            ['title' => 'Abdominal quadrants pain', 'content' => "Quadrant-based localisation narrows the abdominal pain differential before any imaging.\n\n- RUQ: gallbladder, liver\n- LUQ: spleen, gastric\n- RLQ: appendix, ovary, terminal ileum\n- LLQ: sigmoid, ovary\n- Epigastric: pancreas, gastric, referred MI\n\nDo not forget to consider extra-abdominal causes like inferior MI and lower lobe pneumonia presenting as upper abdominal pain."],

            ['title' => 'Gram-positive cocci ladder', 'content' => "Gram-positive cocci can be classified by catalase, coagulase, and hemolysis.\n\n- Catalase positive (staph): coag positive (S. aureus), coag negative (epidermidis, saprophyticus)\n- Catalase negative (strep): beta-hemolytic (GAS, GBS)\n- Catalase negative (strep): alpha-hemolytic (pneumococcus, viridans)\n- Catalase negative (strep): gamma-hemolytic (enterococcus)\n\nThe lab usually finishes the ladder for you, but knowing it lets you predict susceptibilities at the bedside."],
            ['title' => 'Gram-negative quick guide', 'content' => "Gram-negative rods are usually sorted by lactose fermentation and oxidase reaction.\n\n- Lactose fermenters: E. coli, Klebsiella, Enterobacter\n- Non-fermenters: Pseudomonas, Acinetobacter\n- Oxidase positive: Pseudomonas, Neisseria, H. pylori, Campylobacter\n\nThis early sorting drives empiric antibiotic choice well before susceptibilities are back."],
            ['title' => 'Common pathogen by site', 'content' => "Each anatomic site has a short list of usual suspects to anchor empiric therapy.\n\n- Pyelonephritis: E. coli\n- Adult meningitis: pneumococcus, meningococcus\n- Otitis media: pneumococcus, H. flu, Moraxella\n- Cellulitis: strep, staph\n- Septic arthritis: S. aureus, gonococcus in young adults\n\nAdjust empirically when the patient is immunocompromised or has recent healthcare exposure."],
            ['title' => 'Hepatitis serology basics', 'content' => "Hepatitis B serologies tell you the disease phase at a glance once you know the markers.\n\n- HBsAg: active infection\n- Anti-HBs: immunity\n- Anti-HBc IgM: acute infection\n- Anti-HBc IgG: prior exposure\n- HBeAg: high infectivity\n\nFor hepatitis C, screen with anti-HCV and confirm active infection with an HCV RNA viral load."],
            ['title' => 'Tumor marker associations', 'content' => "Tumor markers are not diagnostic, but they help monitor known disease.\n\n- CEA: colorectal cancer\n- CA 19-9: pancreatic cancer\n- AFP: hepatocellular carcinoma, yolk sac tumor\n- PSA: prostate cancer\n- CA-125: ovarian cancer\n- Beta-hCG: choriocarcinoma, germ cell tumors\n- Calcitonin: medullary thyroid cancer\n\nUse markers for surveillance and treatment response, never as a stand-alone screening test."],
            ['title' => 'Histology buzzwords', 'content' => "Histology buzzwords are the highest-yield way to anchor pathology answers.\n\n- Reed-Sternberg cells: Hodgkin lymphoma\n- Auer rods: AML\n- Owl-eye inclusions: CMV\n- Birbeck (tennis-racket) granules: Langerhans cell histiocytosis\n- Psammoma bodies: papillary thyroid, meningioma, serous ovarian, mesothelioma\n\nLink each buzzword to a clinical vignette during review so they stay sticky for exams."],

            ['title' => 'On-call admit checklist', 'content' => "A standard admission checklist keeps overnight admits safe and complete.\n\n- Identifiers and allergies\n- Code status\n- Home and admission medications\n- DVT prophylaxis\n- GI prophylaxis\n- Diet and activity\n- IV fluids\n- Labs and imaging plan\n- Consults\n- Disposition plan\n- Family contact\n\nDocument the plan in the same order each time so the day team can find it instantly."],
            ['title' => 'SOAP note structure', 'content' => "The SOAP framework keeps progress notes organised and readable.\n\n- Subjective: patient-reported information\n- Objective: vitals, exam, labs, imaging\n- Assessment: problem list with reasoning\n- Plan: by problem, including workup and disposition\n\nWriting the assessment by problem rather than by system makes the plan easier for cross-cover to action."],
            ['title' => 'Discharge summary essentials', 'content' => "Every discharge summary should hit a fixed set of essentials so receiving providers do not have to dig.\n\n- Reason for admission\n- Hospital course problem-by-problem\n- Discharge medications with changes highlighted\n- Follow-up appointments\n- Pending labs\n- Code status\n- Patient education provided\n\nClose-the-loop systems for pending labs are critical because diagnoses are often missed after discharge."],
            ['title' => 'Study schedule for shelf', 'content' => "A predictable shelf schedule prevents last-minute cramming.\n\n- Daily UWorld blocks\n- OnlineMedEd videos for weak topics\n- NBME practice exam at 2 weeks out\n- Second NBME at 1 week out\n- High-yield review (Pestana, OnlineMedEd notes) the final week\n\nReview missed UWorld questions in batches rather than individually so themes become visible."],

            ['title' => 'Journal article: SGLT2 in HFpEF', 'content' => "EMPEROR-Preserved was the first major trial to show benefit from any agent in HFpEF.\n\n- Trial: empagliflozin in HFpEF (EF >40%)\n- Primary endpoint: composite of CV death and HF hospitalization\n- Hazard ratio: 0.79\n- Published: NEJM 2021\n\nResults changed guidelines and made SGLT2 inhibitors part of standard HFpEF therapy."],
            ['title' => 'Journal article: PARADIGM-HF', 'content' => "PARADIGM-HF established sacubitril-valsartan over enalapril in chronic HFrEF.\n\n- Population: HFrEF NYHA II-IV\n- Comparator: enalapril\n- Primary endpoint: CV death or HF hospitalization\n- Result: 20% relative risk reduction\n\nThis trial replaced ACE inhibitors with ARNIs as first-line therapy for chronic HFrEF."],
        ];
    }
}
