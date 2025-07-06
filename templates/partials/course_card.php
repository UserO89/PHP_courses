<div class="col-md-4 mb-4 d-flex align-items-stretch">
    <div class="card h-100 position-relative">
        <a href="<?= BASE_PATH ?>/templates/course.php?id=<?= htmlspecialchars($course['id']) ?>">
            <img src="<?= htmlspecialchars($course['image_url'] ?: BASE_PATH . '/assets/img/placeholder.png') ?>"
                 class="card-img-top"
                 alt="<?= htmlspecialchars($course['title']) ?>">
        </a>
        <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?= htmlspecialchars($course['title']) ?></h5>
            <p class="card-text flex-grow-1"><?= htmlspecialchars($course['description']) ?></p>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <span class="badge bg-primary"><?= htmlspecialchars($course['category'] ?? 'N/A') ?></span>
                <?php if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'): ?>
                    <div class="btn-group position-relative" style="z-index:2;">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editCourseModal<?= $course['id'] ?>">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteCourseModal<?= $course['id'] ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div> 
<?php include __DIR__ . '/../../templates/modals/admin/edit_course.php'; ?>
<?php include __DIR__ . '/../../templates/modals/admin/delete_course.php'; ?>