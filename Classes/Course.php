<?php
require_once __DIR__ . '/Database.php';

class Course extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    private function getCategoryIdByName($categoryName)
    {
        $stmt = $this->getConnection()->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->execute([$categoryName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    }

    public function getById($id)
    {
        $stmt = $this->getConnection()->prepare("SELECT c.*, cat.name AS category FROM courses c JOIN categories cat ON c.category_id = cat.id WHERE c.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByTitle($title)
    {
        $stmt = $this->getConnection()->prepare("SELECT c.*, cat.name AS category FROM courses c JOIN categories cat ON c.category_id = cat.id WHERE c.title = ?");
        $stmt->execute([$title]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll()
    {
        $sql = "SELECT c.*, cat.name AS category 
                FROM courses c 
                JOIN categories cat ON c.category_id = cat.id 
                ORDER BY c.created_at DESC";
        $stmt = $this->getConnection()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        try {
            $this->validateCourseData($data);

            $categoryId = $this->getCategoryIdByName($data['category']);
            if (is_null($categoryId)) {
                throw new Exception('Invalid category selected.');
            }

            if ($this->getByTitle($data['title'])) {
                throw new Exception('Course with this title already exists');
            }

            $sql = "INSERT INTO courses (title, description, category_id, duration, price, image_url, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([
                $data['title'],
                $data['description'],
                $categoryId,
                $data['duration'],
                $data['price'],
                $data['image'] ?? null
            ]);

            return $this->getConnection()->lastInsertId();
        } catch (Exception $e) {
            throw new Exception('Error creating course: ' . $e->getMessage());
        }
    }

    public function update($id, $data)
    {
        try {
            $this->validateCourseData($data, true);

            $categoryId = $this->getCategoryIdByName($data['category']);
            if (is_null($categoryId)) {
                throw new Exception('Invalid category selected.');
            }

            $course = $this->getById($id);
            if (!$course) {
                throw new Exception('Course not found');
            }

            $existingCourse = $this->getByTitle($data['title']);
            if ($existingCourse && $existingCourse['id'] != $id && $existingCourse['category_id'] == $categoryId) {
                throw new Exception('Course with this title already exists in this category.');
            }

            $updateData = [
                'title' => $data['title'],
                'description' => $data['description'],
                'category_id' => $categoryId,
                'duration' => $data['duration'],
                'price' => $data['price']
            ];

            if (!empty($data['image'])) {
                $updateData['image_url'] = $data['image'];
            }

            $sql = "UPDATE courses SET ";
            $params = [];
            foreach ($updateData as $key => $value) {
                $sql .= "$key = ?, ";
                $params[] = $value;
            }
            $sql = rtrim($sql, ", ");
            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);

            return true;
        } catch (Exception $e) {
            throw new Exception('Error updating course: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $stmt = $this->getConnection()->prepare("DELETE FROM courses WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            throw new Exception('Error deleting course: ' . $e->getMessage());
        }
    }

    private function validateCourseData($data, $isUpdate = false)
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors[] = 'Title is required';
        }
        if (empty($data['description'])) {
            $errors[] = 'Description is required';
        }
        if (empty($data['category'])) {
            $errors[] = 'Category is required';
        }
        if (empty($data['duration'])) {
            $errors[] = 'Duration is required';
        }
        if (!isset($data['price']) || $data['price'] < 0) {
            $errors[] = 'Valid price is required';
        }

        if (strlen($data['title']) > 255) {
            $errors[] = 'Title must be less than 255 characters';
        }
        if (strlen($data['description']) > 1000) {
            $errors[] = 'Description must be less than 1000 characters';
        }

        if (!is_numeric($data['price'])) {
            $errors[] = 'Price must be a number';
        }

        $categoryId = $this->getCategoryIdByName($data['category']);
        if (is_null($categoryId)) {
            $errors[] = 'Invalid category selected';
        }

        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }
    }

    public function getCount()
    {
        $stmt = $this->getConnection()->query("SELECT COUNT(*) FROM courses");
        return $stmt->fetchColumn();
    }

    public function getCoursesCountLast30Days()
    {
        $stmt = $this->getConnection()->query("SELECT COUNT(*) FROM courses WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        return $stmt->fetchColumn();
    }

    public function getTopRated($limit = 3)
    {
        $sql = "SELECT c.*, cat.name AS category, AVG(r.rating) as avg_rating 
                FROM courses c 
                JOIN categories cat ON c.category_id = cat.id
                LEFT JOIN reviews r ON c.id = r.course_id 
                GROUP BY c.id 
                ORDER BY avg_rating DESC 
                LIMIT ?";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFilteredCourses($filters = [])
    {
        $sql = "SELECT c.*, cat.name AS category 
                FROM courses c 
                JOIN categories cat ON c.category_id = cat.id ";
        $conditions = [];
        $params = [];

        if (!empty($filters['category'])) {
            $categoryId = $this->getCategoryIdByName($filters['category']);
            if ($categoryId) {
                $conditions[] = "c.category_id = ?";
                $params[] = $categoryId;
            }
        }

        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $conditions[] = "c.price >= ?";
            $params[] = $filters['min_price'];
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $conditions[] = "c.price <= ?";
            $params[] = $filters['max_price'];
        }

        if (isset($filters['max_duration']) && is_numeric($filters['max_duration'])) {
            $conditions[] = "c.duration <= ?";
            $params[] = $filters['max_duration'];
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY c.created_at DESC";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllCategories()
    {
        $stmt = $this->getConnection()->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviews($courseId)
    {
        $stmt = $this->getConnection()->prepare('
            SELECT r.*, u.first_name, u.last_name
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.course_id = ?
            ORDER BY r.created_at DESC
        ');
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveCoursesByUserId(int $userId): array
    {
        try {
            $stmt = $this->getConnection()->prepare("
                SELECT uc.progress, c.title, c.description, c.image_url 
                FROM user_courses uc
                JOIN courses c ON uc.course_id = c.id
                WHERE uc.user_id = ? AND uc.is_completed = FALSE
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching active courses for user ID " . $userId . ": " . $e->getMessage());
            return [];
        }
    }
}