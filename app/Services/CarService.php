<?php
declare(strict_types=1);

final class CarService
{
    public function __construct(private PDO $db) {}

    public static function make(): self
    {
        return new self(Database::connection());
    }

    public function allCategories(): array
    {
        return $this->db->query('SELECT * FROM car_categories ORDER BY name')->fetchAll();
    }

    /** Total cars actively offered (excludes retired), for real homepage stats. */
    public function activeCount(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM cars WHERE status != 'retired'")->fetchColumn();
    }

    // ---------------------------------------------------------------
    // Category management (car_categories)
    // ---------------------------------------------------------------
    public function findCategory(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM car_categories WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function categoryCarCounts(): array
    {
        $stmt = $this->db->query(
            "SELECT category_id, COUNT(*) AS total FROM cars WHERE category_id IS NOT NULL GROUP BY category_id"
        );
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function createCategory(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO car_categories (name, slug, description) VALUES (:name, :slug, :description)'
        );
        $stmt->execute([
            ':name'        => $data['name'],
            ':slug'        => $data['slug'] !== '' ? slugify($data['slug']) : slugify($data['name']),
            ':description' => $data['description'] ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateCategory(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE car_categories SET name = :name, slug = :slug, description = :description WHERE id = :id'
        );
        return $stmt->execute([
            ':name'        => $data['name'],
            ':slug'        => $data['slug'] !== '' ? slugify($data['slug']) : slugify($data['name']),
            ':description' => $data['description'] ?: null,
            ':id'          => $id,
        ]);
    }

    public function deleteCategory(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM car_categories WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Cars selected for homepage promotion.
     *
     * "Featured" is editorial and must not disappear merely because a car is
     * currently booked or in maintenance. Only retired cars are hidden from
     * the public homepage. If no cars have been explicitly featured yet, we
     * fall back to the newest public fleet entries so the homepage grid never
     * renders as an empty section while the fleet contains cars.
     */
    public function featured(int $limit = 6): array
    {
        $limit = max(1, min(12, $limit));

        $stmt = $this->db->prepare(
            "SELECT c.*, cc.name AS category_name FROM cars c
             LEFT JOIN car_categories cc ON cc.id = c.category_id
             WHERE c.featured = 1 AND c.status != 'retired'
             ORDER BY c.created_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $cars = $stmt->fetchAll();

        if (!empty($cars)) {
            return $cars;
        }

        $fallback = $this->db->prepare(
            "SELECT c.*, cc.name AS category_name FROM cars c
             LEFT JOIN car_categories cc ON cc.id = c.category_id
             WHERE c.status != 'retired'
             ORDER BY c.created_at DESC
             LIMIT :limit"
        );
        $fallback->bindValue(':limit', $limit, PDO::PARAM_INT);
        $fallback->execute();

        return $fallback->fetchAll();
    }

    /**
     * @param array{category?:string,transmission?:string,seats?:string,max_price?:string} $filters
     */
    public function search(array $filters = []): array
    {
        $sql = "SELECT c.*, cc.name AS category_name, cc.slug AS category_slug
                FROM cars c LEFT JOIN car_categories cc ON cc.id = c.category_id
                WHERE c.status != 'retired'";
        $params = [];

        if (!empty($filters['category'])) {
            $sql .= ' AND cc.slug = :category';
            $params[':category'] = $filters['category'];
        }
        if (!empty($filters['transmission'])) {
            $sql .= ' AND c.transmission = :transmission';
            $params[':transmission'] = $filters['transmission'];
        }
        if (!empty($filters['seats'])) {
            $sql .= ' AND c.seats >= :seats';
            $params[':seats'] = (int) $filters['seats'];
        }
        if (!empty($filters['max_price'])) {
            $sql .= ' AND c.price_per_day <= :max_price';
            $params[':max_price'] = (float) $filters['max_price'];
        }

        $sql .= ' ORDER BY c.featured DESC, c.price_per_day ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, cc.name AS category_name FROM cars c
             LEFT JOIN car_categories cc ON cc.id = c.category_id
             WHERE c.slug = :slug LIMIT 1"
        );
        $stmt->execute([':slug' => $slug]);
        $car = $stmt->fetch();
        return $car ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM cars WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $car = $stmt->fetch();
        return $car ?: null;
    }

    public function gallery(int $carId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM car_images WHERE car_id = :id ORDER BY sort_order');
        $stmt->execute([':id' => $carId]);
        return $stmt->fetchAll();
    }

    public function addGalleryImage(int $carId, string $imagePath): int
    {
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM car_images WHERE car_id = :id');
        $stmt->execute([':id' => $carId]);
        $nextOrder = (int) $stmt->fetchColumn();

        $stmt = $this->db->prepare(
            'INSERT INTO car_images (car_id, image_path, sort_order) VALUES (:car_id, :image_path, :sort_order)'
        );
        $stmt->execute([':car_id' => $carId, ':image_path' => $imagePath, ':sort_order' => $nextOrder]);
        return (int) $this->db->lastInsertId();
    }

    public function findGalleryImage(int $imageId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM car_images WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $imageId]);
        $image = $stmt->fetch();
        return $image ?: null;
    }

    public function deleteGalleryImage(int $imageId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM car_images WHERE id = :id');
        return $stmt->execute([':id' => $imageId]);
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            "SELECT c.*, cc.name AS category_name FROM cars c
             LEFT JOIN car_categories cc ON cc.id = c.category_id
             ORDER BY c.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO cars (category_id, name, slug, brand, model, year, transmission, fuel_type, seats, doors,
                price_per_day, chauffeur_fee_per_day, plate_number, location, description, image_path, status, featured, meta_title, meta_description)
             VALUES (:category_id, :name, :slug, :brand, :model, :year, :transmission, :fuel_type, :seats, :doors,
                :price_per_day, :chauffeur_fee_per_day, :plate_number, :location, :description, :image_path, :status, :featured, :meta_title, :meta_description)'
        );
        $stmt->execute($this->bindable($data));
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE cars SET category_id=:category_id, name=:name, slug=:slug, brand=:brand, model=:model, year=:year,
                transmission=:transmission, fuel_type=:fuel_type, seats=:seats, doors=:doors, price_per_day=:price_per_day,
                chauffeur_fee_per_day=:chauffeur_fee_per_day,
                plate_number=:plate_number, location=:location, description=:description, image_path=:image_path,
                status=:status, featured=:featured, meta_title=:meta_title, meta_description=:meta_description
             WHERE id = :id'
        );
        $data['id'] = $id;
        return $stmt->execute($this->bindable($data) + [':id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM cars WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    private function bindable(array $data): array
    {
        return [
            ':category_id'      => $data['category_id'] ?: null,
            ':name'             => $data['name'],
            ':slug'             => $data['slug'] ?: slugify($data['name']),
            ':brand'            => $data['brand'],
            ':model'            => $data['model'],
            ':year'             => $data['year'] ?: null,
            ':transmission'     => $data['transmission'],
            ':fuel_type'        => $data['fuel_type'],
            ':seats'            => (int) $data['seats'],
            ':doors'            => (int) ($data['doors'] ?? 4),
            ':price_per_day'    => (float) $data['price_per_day'],
            ':chauffeur_fee_per_day' => ($data['chauffeur_fee_per_day'] ?? '') !== '' ? (float) $data['chauffeur_fee_per_day'] : null,
            ':plate_number'     => $data['plate_number'] ?? null,
            ':location'         => $data['location'] ?? 'Nairobi',
            ':description'      => $data['description'] ?? null,
            ':image_path'       => $data['image_path'] ?? null,
            ':status'           => $data['status'] ?? 'available',
            ':featured'         => !empty($data['featured']) ? 1 : 0,
            ':meta_title'       => $data['meta_title'] ?? null,
            ':meta_description' => $data['meta_description'] ?? null,
        ];
    }

    // ---------------------------------------------------------------
    // Fleet / maintenance / compliance
    // ---------------------------------------------------------------

    public function maintenance(int $carId, ?string $status = null): array
    {
        $sql = "SELECT vm.*, CONCAT(u.first_name,' ',u.last_name) AS created_by_name
                FROM vehicle_maintenance vm
                LEFT JOIN users u ON u.id=vm.created_by
                WHERE vm.car_id=:car_id";
        $params=[':car_id'=>$carId];
        if ($status && in_array($status,['scheduled','in_progress','completed','cancelled'],true)) {
            $sql .= " AND vm.status=:status";
            $params[':status']=$status;
        }
        $sql .= " ORDER BY COALESCE(vm.due_date, vm.service_date, '9999-12-31') ASC, vm.created_at DESC";
        $stmt=$this->db->prepare($sql); $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function maintenanceFind(int $id): ?array
    {
        $stmt=$this->db->prepare("SELECT * FROM vehicle_maintenance WHERE id=:id LIMIT 1");
        $stmt->execute([':id'=>$id]); $row=$stmt->fetch(); return $row ?: null;
    }

    public function createMaintenance(int $carId,int $userId,array $data): int
    {
        $stmt=$this->db->prepare(
            "INSERT INTO vehicle_maintenance
            (car_id,maintenance_type,title,description,service_date,due_date,odometer_km,due_odometer_km,cost,vendor,status,created_by)
            VALUES (:car_id,:type,:title,:description,:service_date,:due_date,:odometer,:due_odo,:cost,:vendor,:status,:user_id)"
        );
        $stmt->execute([
            ':car_id'=>$carId,
            ':type'=>in_array($data['maintenance_type']??'service',['service','repair','inspection','tyres','brakes','oil_change','other'],true)?$data['maintenance_type']:'other',
            ':title'=>trim($data['title']??'Maintenance'),
            ':description'=>trim($data['description']??''),
            ':service_date'=>($data['service_date']??'')?:null,
            ':due_date'=>($data['due_date']??'')?:null,
            ':odometer'=>($data['odometer_km']??'')!==''?(float)$data['odometer_km']:null,
            ':due_odo'=>($data['due_odometer_km']??'')!==''?(float)$data['due_odometer_km']:null,
            ':cost'=>max(0,(float)($data['cost']??0)),
            ':vendor'=>trim($data['vendor']??''),
            ':status'=>in_array($data['status']??'scheduled',['scheduled','in_progress','completed','cancelled'],true)?$data['status']:'scheduled',
            ':user_id'=>$userId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateMaintenanceStatus(int $id,string $status,int $userId): bool
    {
        if(!in_array($status,['scheduled','in_progress','completed','cancelled'],true)) return false;
        if($status==='completed'){
            $stmt=$this->db->prepare("UPDATE vehicle_maintenance SET status=:status,completed_by=:user_id,completed_at=NOW() WHERE id=:id");
            return $stmt->execute([':status'=>$status,':user_id'=>$userId,':id'=>$id]);
        }
        $stmt=$this->db->prepare("UPDATE vehicle_maintenance SET status=:status WHERE id=:id");
        return $stmt->execute([':status'=>$status,':id'=>$id]);
    }

    public function documents(int $carId): array
    {
        $stmt=$this->db->prepare(
            "SELECT vd.*, CONCAT(u.first_name,' ',u.last_name) AS uploaded_by_name
             FROM vehicle_documents vd LEFT JOIN users u ON u.id=vd.uploaded_by
             WHERE vd.car_id=:car_id ORDER BY COALESCE(vd.expiry_date,'9999-12-31') ASC, vd.created_at DESC"
        );
        $stmt->execute([':car_id'=>$carId]); return $stmt->fetchAll();
    }

    public function documentFind(int $id): ?array
    {
        $stmt=$this->db->prepare("SELECT * FROM vehicle_documents WHERE id=:id LIMIT 1");
        $stmt->execute([':id'=>$id]); $row=$stmt->fetch(); return $row ?: null;
    }

    public function createDocument(int $carId,int $userId,array $data): int
    {
        $stmt=$this->db->prepare(
            "INSERT INTO vehicle_documents
            (car_id,document_type,title,document_number,file_path,issued_date,expiry_date,notes,uploaded_by)
            VALUES (:car_id,:type,:title,:number,:path,:issued,:expiry,:notes,:user_id)"
        );
        $stmt->execute([
            ':car_id'=>$carId,
            ':type'=>in_array($data['document_type']??'other',['logbook','insurance','inspection','roadworthy','permit','lease','other'],true)?$data['document_type']:'other',
            ':title'=>trim($data['title']??'Vehicle document'),
            ':number'=>trim($data['document_number']??'')?:null,
            ':path'=>$data['file_path']??null,
            ':issued'=>($data['issued_date']??'')?:null,
            ':expiry'=>($data['expiry_date']??'')?:null,
            ':notes'=>trim($data['notes']??''),
            ':user_id'=>$userId,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function markDocumentStatus(int $id,string $status): bool
    {
        if(!in_array($status,['active','expired','replaced'],true)) return false;
        $stmt=$this->db->prepare("UPDATE vehicle_documents SET status=:status WHERE id=:id");
        return $stmt->execute([':status'=>$status,':id'=>$id]);
    }

    public function odometerLogs(int $carId,int $limit=20): array
    {
        $limit=max(1,min(100,$limit));
        $stmt=$this->db->prepare(
            "SELECT vo.*, CONCAT(u.first_name,' ',u.last_name) AS recorded_by_name
             FROM vehicle_odometer_logs vo LEFT JOIN users u ON u.id=vo.recorded_by
             WHERE vo.car_id=:car_id ORDER BY vo.recorded_at DESC LIMIT {$limit}"
        );
        $stmt->execute([':car_id'=>$carId]); return $stmt->fetchAll();
    }

    public function latestOdometer(int $carId): ?array
    {
        $stmt=$this->db->prepare("SELECT * FROM vehicle_odometer_logs WHERE car_id=:car_id ORDER BY recorded_at DESC,id DESC LIMIT 1");
        $stmt->execute([':car_id'=>$carId]); $row=$stmt->fetch(); return $row ?: null;
    }

    public function logOdometer(int $carId,int $userId,float $reading,string $type='manual',?int $bookingId=null,string $notes=''): int
    {
        if(!in_array($type,['manual','checkout','return','service'],true)) $type='manual';
        $latest=$this->latestOdometer($carId);
        if($latest && $reading < (float)$latest['reading_km']) throw new RuntimeException('Odometer reading cannot be lower than the latest recorded reading.');
        $stmt=$this->db->prepare(
            "INSERT INTO vehicle_odometer_logs (car_id,booking_id,reading_km,reading_type,recorded_by,notes)
             VALUES (:car_id,:booking_id,:reading,:type,:user_id,:notes)"
        );
        $stmt->execute([':car_id'=>$carId,':booking_id'=>$bookingId,':reading'=>$reading,':type'=>$type,':user_id'=>$userId,':notes'=>trim($notes)]);
        return (int)$this->db->lastInsertId();
    }

    public function fleetAlerts(): array
    {
        $warningDays=max(1,(int)setting('fleet','document_expiry_warning_days','30'));
        $maintenanceDays=max(1,(int)setting('fleet','maintenance_due_warning_days','14'));
        $stmt=$this->db->prepare(
            "SELECT vd.id,'document' AS alert_type,vd.car_id,c.name AS car_name,c.plate_number,vd.title AS item,
                    vd.expiry_date AS due_date,vd.status
             FROM vehicle_documents vd JOIN cars c ON c.id=vd.car_id
             WHERE vd.status='active' AND vd.expiry_date IS NOT NULL
               AND vd.expiry_date <= DATE_ADD(CURDATE(), INTERVAL :doc_days DAY)
            UNION ALL
            SELECT vm.id,'maintenance' AS alert_type,vm.car_id,c.name,c.plate_number,vm.title,
                   vm.due_date,vm.status
             FROM vehicle_maintenance vm JOIN cars c ON c.id=vm.car_id
             WHERE vm.status IN ('scheduled','in_progress') AND vm.due_date IS NOT NULL
               AND vm.due_date <= DATE_ADD(CURDATE(), INTERVAL :maint_days DAY)
             ORDER BY due_date ASC"
        );
        $stmt->bindValue(':doc_days',$warningDays,PDO::PARAM_INT);
        $stmt->bindValue(':maint_days',$maintenanceDays,PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function fleetStats(): array
    {
        $row=$this->db->query(
            "SELECT
             COUNT(*) total,
             SUM(status='available') available,
             SUM(status='booked') booked,
             SUM(status='maintenance') maintenance,
             SUM(status='retired') retired
             FROM cars"
        )->fetch() ?: [];
        $row['maintenance_cost_month']=(float)$this->db->query(
            "SELECT COALESCE(SUM(cost),0) FROM vehicle_maintenance
             WHERE status='completed' AND completed_at >= DATE_FORMAT(CURDATE(),'%Y-%m-01')"
        )->fetchColumn();
        return $row;
    }

    // ---------------------------------------------------------------
    // Chauffeur ("with driver") pricing
    // ---------------------------------------------------------------

    /**
     * Resolves the chauffeur fee/day that applies to this car, in order:
     * 1. the car's own override (cars.chauffeur_fee_per_day)
     * 2. a rate configured for the car's location (chauffeur_rates)
     * 3. the sitewide default (settings: general.default_chauffeur_fee_per_day)
     * 4. 0.0 if none of the above are set
     */
    public function effectiveChauffeurFee(array $car): float
    {
        if (!empty($car['chauffeur_fee_per_day'])) {
            return (float) $car['chauffeur_fee_per_day'];
        }

        if (!empty($car['location'])) {
            $stmt = $this->db->prepare('SELECT rate_per_day FROM chauffeur_rates WHERE location = :location LIMIT 1');
            $stmt->execute([':location' => $car['location']]);
            $rate = $stmt->fetchColumn();
            if ($rate !== false) {
                return (float) $rate;
            }
        }

        return (float) setting('general', 'default_chauffeur_fee_per_day', '0');
    }

    public function chauffeurRates(): array
    {
        return $this->db->query('SELECT * FROM chauffeur_rates ORDER BY location')->fetchAll();
    }

    public function findChauffeurRate(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM chauffeur_rates WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createChauffeurRate(string $location, float $ratePerDay): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO chauffeur_rates (location, rate_per_day) VALUES (:location, :rate)'
        );
        $stmt->execute([':location' => $location, ':rate' => $ratePerDay]);
        return (int) $this->db->lastInsertId();
    }

    public function updateChauffeurRate(int $id, string $location, float $ratePerDay): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE chauffeur_rates SET location = :location, rate_per_day = :rate WHERE id = :id'
        );
        return $stmt->execute([':location' => $location, ':rate' => $ratePerDay, ':id' => $id]);
    }

    public function deleteChauffeurRate(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM chauffeur_rates WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}
