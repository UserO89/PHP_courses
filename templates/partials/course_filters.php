<div class="card mb-4">
    <div class="card-header">Filter Courses</div>
    <div class="card-body">
        <form action="<?= BASE_PATH ?>/templates/courses.php" method="GET">
            <div class="mb-3">
                <label for="categoryFilter" class="form-label">Category</label>
                <select class="form-select" id="categoryFilter" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['name']) ?>"
                            <?= (isset($_GET['category']) && $_GET['category'] == $cat['name']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="minPrice" class="form-label">Min Price</label>
                <input type="number" step="0.01" class="form-control" id="minPrice" name="min_price" 
                    value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="maxPrice" class="form-label">Max Price</label>
                <input type="number" step="0.01" class="form-control" id="maxPrice" name="max_price" 
                    value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="durationFilter" class="form-label">Max Duration (hours)</label> 
                <input type="number" class="form-control" id="durationFilter" name="max_duration" 
                    value="<?= htmlspecialchars($_GET['max_duration'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <a href="<?= BASE_PATH ?>/templates/courses.php" class="btn btn-secondary mt-2">Clear Filters</a>
        </form>
    </div>
</div> 