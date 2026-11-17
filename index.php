<?php
require_once __DIR__ . '/config/database.php';

$message = '';
$error = '';

/* =========================
   PROCESS FORMS
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {

        /* ADD STUDENT */
        if (isset($_POST['add_student'])) {
            $name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $year = trim($_POST['academic_year'] ?? '');

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Enter a valid student name and email.");
            }

            $stmt = $pdo->prepare(
                "INSERT INTO students (full_name, email, phone, academic_year)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$name, $email, $phone, $year]);

            header("Location: index.php?tab=students&success=student");
            exit;
        }

        /* ADD SPECIALIZATION */
        if(isset($_POST['add_specialization'])) {
            $code = trim($_POST['spec_code'] ?? '');
            $name = trim($_POST['spec_name'] ?? '');
            $description = trim($_POST['spec_description'] ?? '');

            if ($code === '' || $name === '') {
                throw new Exception("Enter specialization code and name.");
            }

            $stmt = $pdo->prepare(
                "INSERT INTO specializations (code, name, description, is_active)
                 VALUES (?, ?, ?, 1)"
            );
            $stmt->execute([$code, $name, $description]);

            header("Location: index.php?tab=specializations&success=specialization");
            exit;
        }

        /* ADD CRITERION */
        if (isset($_POST['add_criterion'])) {
            $code = trim($_POST['criterion_code'] ?? '');
            $name = trim($_POST['criterion_name'] ?? '');
            $type = $_POST['criterion_type'] ?? '';
            $weight = (float)($_POST['weight'] ?? 0);
            $description = trim($_POST['criterion_description'] ?? '');

            if (
                $code === '' ||
                $name === '' ||
                !in_array($type, ['maximize', 'minimize'], true) ||
                $weight <= 0
            ) {
                throw new Exception("Enter valid criterion information.");
            }

            $stmt = $pdo->prepare(
                "INSERT INTO criteria
                 (code, name, criterion_type, weight, description)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$code, $name, $type, $weight, $description]);

            header("Location: index.php?tab=criteria&success=criterion");
            exit;
        }
          /* =========================
           ADD EVALUATION
           ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_evaluation'])) {

    $student_id = (int)($_POST['student_id'] ?? 0);
    $specialization_id = (int)($_POST['specialization_id'] ?? 0);
    $criterion_id = (int)($_POST['criterion_id'] ?? 0);
    $score = (float)($_POST['score'] ?? 0);

    if (
        $student_id <= 0 ||
        $specialization_id <= 0 ||
        $criterion_id <= 0 ||
        $score < 1 ||
        $score > 10
    ) {
        $error = "Please enter valid evaluation information. Score must be between 1 and 10.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO evaluations
                (student_id, specialization_id, criterion_id, score)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                score = VALUES(score)
            ");

            $stmt->execute([
                $student_id,
                $specialization_id,
                $criterion_id,
                $score
            ]);

            $message = "Evaluation saved successfully!";

        } catch (PDOException $e) {
            $error = "Error saving evaluation: " . $e->getMessage();
        }
    }
}

/* GET EVALUATIONS */

$evaluationRows = $pdo->query("
    SELECT
        evaluations.id,
        students.full_name,
        specializations.name AS specialization_name,
        criteria.name AS criterion_name,
        evaluations.score
    FROM evaluations
    INNER JOIN students
        ON evaluations.student_id = students.id
    INNER JOIN specializations
        ON evaluations.specialization_id = specializations.id
    INNER JOIN criteria
        ON evaluations.criterion_id = criteria.id
    ORDER BY evaluations.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

            // Check that the selected records exist
            $check = $pdo->prepare(
                "SELECT COUNT(*) FROM students WHERE id = ?"
            );
            $check->execute([$studentId]);
           
     if (!$check->fetchColumn()) {
                throw new Exception("Selected student does not exist.");
            }

            $check = $pdo->prepare(
                "SELECT COUNT(*) FROM specializations
                 WHERE id = ? AND is_active = 1"
            );
            $check->execute([$specializationId]);

            if (!$check->fetchColumn()) {
                throw new Exception("Selected specialization does not exist.");
            }

            // Check whether this evaluation already exists
            $stmt = $pdo->prepare(
                "SELECT id FROM evaluations
                 WHERE student_id = ?
                 AND specialization_id = ?
                 AND criterion_id = ?
                 LIMIT 1"
            );

            $stmt->execute([
                $studentId,
                $specializationId,
                $criterionId
            ]);

            $existing = $stmt->fetchColumn();

            if ($existing) {

                $stmt = $pdo->prepare(
                    "UPDATE evaluations
                     SET score = ?, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?"
                );

                $stmt->execute([(float)$score, $existing]);

            } else {

                $stmt = $pdo->prepare(
                    "INSERT INTO evaluations
                     (student_id, specialization_id, criterion_id, score)
                     VALUES (?, ?, ?, ?)"
                );

                $stmt->execute([
                    $studentId,
                    $specializationId,
                    $criterionId,
                    (float)$score
                ]);
            }

            header(
                "Location: index.php?tab=evaluations&student_id=" .
                $studentId . "&success=evaluation"
            );
            exit;

        } catch (Throwable $e) {

            $error = $e->getMessage();

        }

    }
/* =========================
   SUCCESS MESSAGES
========================= */

$success = $_GET['success'] ?? '';

$successMessages = [
    'student' => 'Student added successfully!',
    'specialization' => 'Specialization added successfully!',
    'criterion' => 'Criterion added successfully!',
    'evaluation' => 'Evaluation saved successfully!'
];

if (isset($successMessages[$success])) {
    $message = $successMessages[$success];
}

/* =========================
   HELPER FUNCTION
========================= */

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* =========================
   GET DATA
========================= */

$students = $pdo->query(
    "SELECT * FROM students ORDER BY id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$specializations = $pdo->query(
    "SELECT * FROM specializations
     WHERE is_active = 1
     ORDER BY id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$criteria = $pdo->query(
    "SELECT * FROM criteria ORDER BY id ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$evaluationsCount = (int)$pdo->query(
    "SELECT COUNT(*) FROM evaluations"
)->fetchColumn();

$studentsCount = count($students);
$specializationsCount = count($specializations);
$criteriaCount = count($criteria);

$studentId = (int)($_GET['student_id'] ?? 0);if ($studentId === 0 && !empty($students)) {
    $studentId = (int)$students[0]['id'];
}

$activeTab = $_GET['tab'] ?? 'dashboard';

$allowedTabs = [
    'dashboard',
    'students',
    'specializations',
    'criteria',
    'evaluations',
    'ranking'
];

if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'dashboard';
}

/* =========================
   WSM CALCULATION
========================= */

$ranking = [];
$incomplete = [];

if ($studentId > 0 && !empty($criteria) && !empty($specializations)) {

    $stmt = $pdo->prepare(
        "SELECT specialization_id, criterion_id, score
         FROM evaluations
         WHERE student_id = ?"
    );

    $stmt->execute([$studentId]);

    $allEvaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $evaluationMap = [];

    foreach ($allEvaluations as $ev) {
        $specId = (int)$ev['specialization_id'];
        $criterionId = (int)$ev['criterion_id'];

        $evaluationMap[$specId][$criterionId] = (float)$ev['score'];
    }

    $complete = [];

    foreach ($specializations as $spec) {

        $specId = (int)$spec['id'];
        $scores = [];
        $isComplete = true;

        foreach ($criteria as $criterion) {

            $criterionId = (int)$criterion['id'];

            if (!isset($evaluationMap[$specId][$criterionId])) {
                $isComplete = false;
                break;
            }

            $scores[$criterionId] = $evaluationMap[$specId][$criterionId];
        }

        if ($isComplete) {
            $complete[] = [
                'name' => $spec['name'],
                'scores' => $scores,
                'total' => 0
            ];
        } else {
            $incomplete[] = $spec['name'];
        }
    }

    // Keep your existing WSM calculation here.
}

    if (!empty($complete)) {
        $totalWeight = 0;

        foreach ($criteria as $criterion) {
            $totalWeight += (float)$criterion['weight'];
        }

        if ($totalWeight > 0) {
            foreach ($criteria as $criterion) {
                $cid = (int)$criterion['id'];
                $values = [];

                foreach ($complete as $item) {
                    $values[] = $item['scores'][$cid];
                }

                $maxValue = max($values);
                $minValue = min($values);

                $type = strtolower($criterion['criterion_type']);
                $weight = (float)$criterion['weight'] / $totalWeight;

                foreach ($complete as &$item) {
                    $value = $item['scores'][$cid];

                    if (strpos($type, 'min') !== false) {
                        if ($value == 0) {
                            $normalized = ($minValue == 0) ? 1 : 0;
                        } else {
                            $normalized = $minValue / $value;
                        }
                    } else {
                        $normalized = ($maxValue == 0)
                            ? 1
                            : $value / $maxValue;
                    }$item['total'] += $normalized * $weight;
                }

                unset($item);
            }

            usort($complete, function ($a, $b) {
                return $b['total'] <=> $a['total'];
            });

            $rank = 1;

            foreach ($complete as $item) {
                $ranking[] = [
                    'rank' => $rank++,
                    'name' => $item['name'],
                    'score' => $item['total']
                ];
            }
        }
    }


$recommended = $ranking[0] ?? null;

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Specialization DSS</title><style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f3f5fa;
    color: #26334b;
}

header {
    background: #243b65;
    color: white;
    padding: 25px;
}

header h1 {
    margin: 0 0 8px;
}

nav {
    background: white;
    padding: 15px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    box-shadow: 0 2px 8px #ddd;
}

nav a {
    text-decoration: none;
    color: #3159a5;
    padding: 10px 14px;
    border-radius: 7px;
    font-weight: bold;
}

nav a.active,
nav a:hover {
    background: #3159a5;
    color: white;
}

.container {
    padding: 25px;
    max-width: 1200px;
    margin: auto;
}

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.card, .section {
    background: white;
    padding: 22px;
    border-radius: 12px;
    margin-bottom: 22px;
    box-shadow: 0 3px 12px rgba(0,0,0,.05);
}

.card h3 {
    color: #777;
    font-size: 15px;
}.number {
    font-size: 30px;
    font-weight: bold;
    color: #3159a5;
}

input, select, textarea {
    width: 100%;
    padding: 12px;
    margin: 8px 0 16px;
    border: 1px solid #ccd3df;
    border-radius: 7px;
    font-size: 15px;
}

textarea {
    min-height: 75px;
    resize: vertical;
}

label {
    font-weight: bold;
}

button {
    background: #3159a5;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    font-size: 15px;
}

button:hover {
    background: #243b65;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

th, td {
    padding: 13px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

th {
    background: #edf2fc;
}

.success {
    background: #e2f5e9;
    color: #18743b;
    padding: 14px;
    border-radius: 7px;
    margin-bottom: 18px;
}

.error {
    background: #ffe6e6;
    color: #b42318;
    padding: 14px;
    border-radius: 7px;
    margin-bottom: 18px;
}

.recommended {
    background: #e5f5e9;
    padding: 20px;
    border-left: 5px solid #28a745;
    border-radius: 8px;
    margin-top: 20px;
}.warning {
    background: #fff3d6;
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
}

@media(max-width: 650px) {
    .container {
        padding: 12px;
    }

    .section {
        overflow-x: auto;
    }
}
</style>
</head>

<body>

<header>
    <h1>Specialization DSS</h1>
    <p>Decision Support System for University Specialization Selection</p>
</header>

<nav>
    <a href="index.php?tab=dashboard"
       class="<?= $activeTab === 'dashboard' ? 'active' : '' ?>">
       Dashboard
    </a>

    <a href="index.php?tab=students"
       class="<?= $activeTab === 'students' ? 'active' : '' ?>">
       Students
    </a>

    <a href="index.php?tab=specializations"
       class="<?= $activeTab === 'specializations' ? 'active' : '' ?>">
       Specializations
    </a>

    <a href="index.php?tab=criteria"
       class="<?= $activeTab === 'criteria' ? 'active' : '' ?>">
       Criteria
    </a>

    <a href="index.php?tab=evaluations"
       class="<?= $activeTab === 'evaluations' ? 'active' : '' ?>">
       Evaluations
    </a>

    <a href="index.php?tab=ranking"
       class="<?= $activeTab === 'ranking' ? 'active' : '' ?>">
       Results & Ranking
    </a></nav>

<div class="container">

    <?php if ($message): ?>
        <div class="success"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <!-- DASHBOARD -->
    <?php if ($activeTab === 'dashboard'): ?>

        <h2>Dashboard Overview</h2>

        <div class="cards">
            <div class="card">
                <h3>Total Students</h3>
                <div class="number"><?= $studentsCount ?></div>
            </div>

            <div class="card">
                <h3>Specializations</h3>
                <div class="number"><?= $specializationsCount ?></div>
            </div>

            <div class="card">
                <h3>Criteria</h3>
                <div class="number"><?= $criteriaCount ?></div>
            </div>

            <div class="card">
                <h3>Evaluations</h3>
                <div class="number"><?= $evaluationsCount ?></div>
            </div>
        </div>

        <div class="section">
            <h2>Welcome to Specialization DSS</h2>
            <p>
                Manage students, specializations, criteria and evaluations.
                Calculate the best specialization using the WSM method.
            </p>
        </div>

    <?php endif; ?>

    <!-- STUDENTS -->
    <?php if ($activeTab === 'students'): ?><div class="section">
            <h2>Add New Student</h2>

            <form method="POST">
                <label>Full Name</label>
                <input type="text" name="full_name"
                       placeholder="Student full name" required>

                <label>Email</label>
                <input type="email" name="email"
                       placeholder="Student email" required>

                <label>Phone</label>
                <input type="text" name="phone"
                       placeholder="Phone number">

                <label>Academic Year</label>
                <input type="text" name="academic_year"
                       placeholder="Example: 2026-2027">

                <button type="submit" name="add_student">
                    Add Student
                </button>
            </form>
        </div>

        <div class="section">
            <h2>Students List</h2>

            <table>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Academic Year</th>
                </tr><?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= e($student['id']) ?></td>
                        <td><?= e($student['full_name']) ?></td>
                        <td><?= e($student['email']) ?></td>
                        <td><?= e($student['phone']) ?></td>
                        <td><?= e($student['academic_year']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

    <?php endif; ?>

    <!-- SPECIALIZATIONS -->
    <?php if ($activeTab === 'specializations'): ?>

        <div class="section">
            <h2>Add New Specialization</h2>

            <form method="POST">
                <label>Specialization Code</label>
                <input type="text" name="spec_code"
                       placeholder="Example: CS01" required>

                <label>Specialization Name</label>
                <input type="text" name="spec_name"
                       placeholder="Example: Software Engineering" required>

                <label>Description</label>
                <textarea name="spec_description"
                          placeholder="Description"></textarea>

                <button type="submit" name="add_specialization">
                    Add Specialization
                </button>
            </form>
        </div>

        <div class="section">
            <h2>Specializations List</h2>

            <table>
                <tr>
                    <th>ID</th><th>Code</th>
                    <th>Name</th>
                    <th>Description</th>
                </tr>

                <?php foreach ($specializations as $spec): ?>
                    <tr>
                        <td><?= e($spec['id']) ?></td>
                        <td><?= e($spec['code']) ?></td>
                        <td><?= e($spec['name']) ?></td>
                        <td><?= e($spec['description']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

    <?php endif; ?>

    <!-- CRITERIA -->
    <?php if ($activeTab === 'criteria'): ?>

        <div class="section">
            <h2>Add New Criterion</h2>

            <form method="POST">
                <label>Criterion Code</label>
                <input type="text" name="criterion_code"
                       placeholder="Example: C01" required>

                <label>Criterion Name</label>
                <input type="text" name="criterion_name"
                       placeholder="Example: Difficulty" required>

                <label>Criterion Type</label>
                <select name="criterion_type" required>
                    <option value="maximize">Maximize</option>
                    <option value="minimize">Minimize</option>
                </select>

                <label>Weight</label>
                <input type="number" name="weight"
                       min="0.01" step="0.01"placeholder="Example: 20" required>

                <label>Description</label>
                <textarea name="criterion_description"
                          placeholder="Criterion description"></textarea>

                <button type="submit" name="add_criterion">
                    Add Criterion
                </button>
            </form>
        </div>

        <div class="section">
            <h2>Criteria List</h2>

            <table>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Weight</th>
                </tr>

                <?php foreach ($criteria as $criterion): ?>
                    <tr>
                        <td><?= e($criterion['id']) ?></td>
                        <td><?= e($criterion['code']) ?></td>
                        <td><?= e($criterion['name']) ?></td>
                        <td><?= e($criterion['criterion_type']) ?></td>
                        <td><?= e($criterion['weight']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

    <?php endif; ?>

    <!-- EVALUATIONS -->
<?php if ($activeTab === 'evaluations'): ?>

    <div class="section">

        <h2>Add Evaluation</h2>

        <?php if (!empty($message)): ?>
            <div class="success">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if (
            empty($students) ||
            empty($specializations) ||
            empty($criteria)
        ): ?>

            <div class="warning">
                Please add at least one student, one active
                specialization and one criterion before
                adding evaluations.
            </div>

        <?php else: ?>

            <form method="POST">

                <label>Select Student</label>

                <select name="student_id" required>
                    <option value="">Choose Student</option>

                    <?php foreach ($students as $student): ?>
                        <option value="<?= (int)$student['id'] ?>">
                            <?= e($student['full_name']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>


                <label>Select Specialization</label>

                <select name="specialization_id" required>
                    <option value="">Choose Specialization</option>

                    <?php foreach ($specializations as $spec): ?>
                        <option value="<?= (int)$spec['id'] ?>">
                            <?= e($spec['name']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>


                <label>Select Criterion</label>

                <select name="criterion_id" required>
                    <option value="">Choose Criterion</option>

                    <?php foreach ($criteria as $criterion): ?>
                        <option value="<?= (int)$criterion['id'] ?>">
                            <?= e($criterion['name']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>


                <label>Score (1 - 10)</label>

                <input
                    type="number"
                    name="score"
                    min="1"
                    max="10"
                    step="0.01"
                    placeholder="Enter score"
                    required
                >

                <button type="submit" name="add_evaluation">
                    Save Evaluation
                </button>

            </form>

        <?php endif; ?>

    </div>


    <div class="section">

        <h2>Evaluations List</h2>

        <table>

            <thead>
                <tr>
                    <th>Student</th>
                    <th>Specialization</th>
                    <th>Criterion</th>
                    <th>Score</th>
                </tr>
            </thead>

            <tbody>

                <?php if (!empty($evaluationRows)): ?>

                    <?php foreach ($evaluationRows as $row): ?>

                        <tr>
                            <td>
                                <?= e($row['full_name']) ?>
                            </td>

                            <td>
                                <?= e($row['specialization_name']) ?>
                            </td>

                            <td>
                                <?= e($row['criterion_name']) ?>
                            </td>

                            <td>
                                <?= e($row['score']) ?>/10
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4">
                            No evaluations found.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>
    <?php endif; ?>
    <!-- RESULTS AND RANKING -->
    <?php if ($activeTab === 'ranking'): ?>

        <div class="section">
            <h2>Results & Ranking - WSM</h2>

            <form method="GET">
                <input type="hidden" name="tab" value="ranking">

                <label>Select Student</label>
                <select name="student_id" required>
                    <option value="">Choose Student</option>

                    <?php foreach ($students as $student): ?>
                        <option value="<?= (int)$student['id'] ?>"
                            <?= $studentId === (int)$student['id'] ? 'selected' : '' ?>>
                            <?= e($student['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Calculate Ranking</button>
            </form>

            <?php if ($recommended): ?>

                <div class="recommended">
                    <h3>Best Recommended Specialization</h3>
                    <h2><?= e($recommended['name']) ?></h2>

                    <p>
                        WSM Score:
                        <strong>
                            <?= number_format($recommended['score'], 4) ?>
                        </strong>
                    </p>
                </div>

            <?php else: ?>
                <p>No complete ranking available yet.</p>
            <?php endif; ?><?php if (!empty($ranking)): ?>

                <h3>Specialization Ranking</h3>

                <table>
                    <tr>
                        <th>Rank</th>
                        <th>Specialization</th>
                        <th>WSM Score</th>
                    </tr>

                    <?php foreach ($ranking as $item): ?>
                        <tr>
                            <td><?= e($item['rank']) ?></td>
                            <td><?= e($item['name']) ?></td>
                            <td><?= number_format($item['score'], 4) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

            <?php endif; ?>

            <?php if (!empty($incomplete)): ?>

                <div class="warning">
                    <strong>Incomplete Evaluations:</strong>

                    <ul>
                        <?php foreach ($incomplete as $name): ?>
                            <li><?= e($name) ?></li>
                        <?php endforeach; ?>
                    </ul>

                    Complete all criteria evaluations for these specializations.
                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>

</div>

</body>
</html>