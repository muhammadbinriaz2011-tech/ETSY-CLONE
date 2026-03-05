<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
 
require_once 'db.php';
 
$pdo->exec("CREATE TABLE IF NOT EXISTS listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(1000),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 
// BUY NOW
if (isset($_GET['buy']) && is_numeric($_GET['buy'])) {
    $id = (int)$_GET['buy'];
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) die("Item not found.");
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Confirmed – Etsy Clone</title>
    <style>
        :root {
            --neon-green: #4ade80;
            --space: #0f0c29;
            --glass: rgba(25, 20, 60, 0.25);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(to right, var(--space), #302b63, #24243e);
            color: white;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(74, 222, 128, 0.1) 0%, transparent 20%),
                url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='50' cy='50' r='1' fill='%234ade80' fill-opacity='0.3'/%3E%3C/svg%3E");
            pointer-events: none;
        }
        .container {
            background: var(--glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            max-width: 600px;
            border: 1px solid rgba(74, 222, 128, 0.4);
            box-shadow: 0 0 30px rgba(74, 222, 128, 0.3);
            position: relative;
            z-index: 2;
        }
        .icon {
            font-size: 5rem;
            color: var(--neon-green);
            margin-bottom: 20px;
            text-shadow: 0 0 20px rgba(74, 222, 128, 0.7);
        }
        h1 {
            font-size: 2.2rem;
            margin: 15px 0;
            color: white;
        }
        .item {
            margin: 20px 0;
            padding: 15px;
            background: rgba(15,12,41,0.4);
            border-radius: 12px;
        }
        .price {
            font-size: 1.5rem;
            color: var(--neon-green);
            margin: 15px 0;
            font-weight: bold;
        }
        .back a {
            color: #86a8e7;
            text-decoration: none;
            font-weight: bold;
            margin-top: 30px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">✅</div>
        <h1>Order Confirmed!</h1>
        <p>Your purchase has been processed successfully.</p>
        <div class="item">
            <div><strong><?= htmlspecialchars($item['title']) ?></strong></div>
            <div class="price">$<?= number_format($item['price'], 2) ?></div>
        </div>
        <div class="back">
            <a href="index.php">← Back to Shop</a>
        </div>
    </div>
</body>
</html>
    <?php
    exit;
}
 
// DELETE
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_url FROM listings WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if ($item && $item['image_url'] && strpos($item['image_url'], '/etsy/uploads/') !== false) {
        $filename = basename($item['image_url']);
        $file_path = __DIR__ . '/uploads/' . $filename;
        if (file_exists($file_path)) unlink($file_path);
    }
    $pdo->prepare("DELETE FROM listings WHERE id = ?")->execute([$id]);
    header("Location: index.php?deleted=1");
    exit;
}
 
// ADD ITEM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['title'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $image_url = '';
 
    if (!empty($_FILES['image']['name'])) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed) && $file['error'] === 0 && $file['size'] < 5000000) {
            $new_name = 'item_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $path = $upload_dir . $new_name;
            if (move_uploaded_file($file['tmp_name'], $path)) {
                $image_url = '/etsy/uploads/' . $new_name;
            }
        }
    }
    if (empty($image_url) && !empty($_POST['image_url'])) $image_url = trim($_POST['image_url']);
    if ($title && $description && $price > 0) {
        $stmt = $pdo->prepare("INSERT INTO listings (title, description, price, image_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $description, $price, $image_url]);
        header("Location: index.php?added=1");
        exit;
    }
}
 
// VIEW ITEM
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $id = (int)$_GET['view'];
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) die("Item not found.");
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($item['title']) ?> – Etsy Clone</title>
    <style>
        :root {
            --neon-purple: #d16ba5;
            --neon-cyan: #86a8e7;
            --neon-green: #4ade80;
            --space: #0f0c29;
            --glass: rgba(25, 20, 60, 0.25);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(to right, var(--space), #302b63, #24243e);
            color: white;
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                radial-gradient(circle at 10% 20%, rgba(209,107,165,0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(134,168,231,0.1) 0%, transparent 20%),
                url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='50' cy='50' r='2' fill='%23d16ba5' fill-opacity='0.2'/%3E%3C/svg%3E");
            pointer-events: none;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(209,107,165,0.3);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(209,107,165,0.2);
            position: relative;
            overflow: hidden;
        }
        .back a {
            color: var(--neon-cyan);
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 20px;
            display: inline-block;
        }
        img {
            width: 100%;
            height: 350px;
            object-fit: cover;
            border-radius: 16px;
            margin: 20px 0;
            box-shadow: 0 0 15px rgba(134,168,231,0.3);
        }
        h1 {
            font-size: 2.2rem;
            margin: 15px 0;
            color: white;
        }
        .price {
            font-size: 1.8rem;
            font-weight: bold;
            color: #ffffff;
            margin: 15px 0;
            text-shadow: 0 0 10px rgba(209,107,165,0.7);
        }
        .desc {
            line-height: 1.7;
            color: #e0e0ff;
            margin: 20px 0;
        }
        .actions {
            margin-top: 25px;
            display: flex;
            gap: 15px;
        }
        .btn {
            padding: 14px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
            flex: 1;
            transition: all 0.3s ease;
        }
        .buy {
            background: rgba(74, 222, 128, 0.2);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
        }
        .buy:hover {
            background: rgba(74, 222, 128, 0.4);
            box-shadow: 0 0 15px rgba(74, 222, 128, 0.6);
            transform: scale(1.03);
        }
        .delete {
            background: rgba(209, 107, 165, 0.2);
            color: var(--neon-purple);
            border: 1px solid var(--neon-purple);
            padding: 14px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .delete:hover {
            background: rgba(209, 107, 165, 0.4);
            box-shadow: 0 0 15px rgba(209,107,165,0.6);
            transform: scale(1.03);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back"><a href="index.php">← Back to Etsy Clone</a></div>
        <?php if (!empty($item['image_url'])): ?>
            <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
        <?php endif; ?>
        <h1><?= htmlspecialchars($item['title']) ?></h1>
        <div class="price">$<?= number_format($item['price'], 2) ?></div>
        <p class="desc"><?= nl2br(htmlspecialchars($item['description'])) ?></p>
        <div class="actions">
            <a href="index.php?buy=<?= $item['id'] ?>" class="btn buy">🛒 Buy Now</a>
            <a href="index.php?delete=<?= $item['id'] ?>" class="delete" onclick="return confirm('Are you sure?')">🗑️ Delete</a>
        </div>
    </div>
</body>
</html>
    <?php
    exit;
}
 
// MAIN SHOP PAGE
$listings = $pdo->query("SELECT * FROM listings ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
 
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etsy Clone – Futuristic</title>
    <style>
        :root {
            --neon-purple: #d16ba5;
            --neon-cyan: #86a8e7;
            --neon-green: #4ade80;
            --space: #0f0c29;
            --glass: rgba(25, 20, 60, 0.25);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(to right, var(--space), #302b63, #24243e);
            color: white;
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                radial-gradient(circle at 10% 20%, rgba(209,107,165,0.1) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(134,168,231,0.1) 0%, transparent 20%),
                url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='50' cy='50' r='2' fill='%23d16ba5' fill-opacity='0.2'/%3E%3C/svg%3E");
            pointer-events: none;
        }
        h1 {
            text-align: center;
            font-size: 2.6rem;
            margin: 30px 0;
            background: linear-gradient(90deg, var(--neon-purple), var(--neon-cyan));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-shadow: 0 0 10px rgba(209,107,165,0.6);
            letter-spacing: 1px;
        }
        .form-box {
            max-width: 650px;
            margin: 0 auto 40px;
            background: var(--glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(134,168,231,0.3);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 0 25px rgba(134,168,231,0.2);
            position: relative;
            overflow: hidden;
        }
        .form-box::after {
            content: "";
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            background: linear-gradient(45deg, var(--neon-cyan), var(--neon-purple), var(--neon-cyan));
            z-index: -1;
            border-radius: 22px;
            opacity: 0.6;
        }
        .form-box input,
        .form-box textarea {
            width: 100%; padding: 14px; margin: 12px 0;
            background: rgba(15, 12, 41, 0.5);
            border: 1px solid rgba(134,168,231,0.4);
            border-radius: 12px;
            color: white;
            font-size: 16px;
        }
        .form-box input::placeholder,
        .form-box textarea::placeholder {
            color: rgba(200, 200, 255, 0.6);
        }
        .form-box button {
            background: linear-gradient(45deg, var(--neon-purple), var(--neon-cyan));
            color: white;
            padding: 14px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            width: 100%;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        .form-box button:hover {
            box-shadow: 0 0 20px rgba(209,107,165,0.8);
            transform: translateY(-2px);
        }
        .upload-box {
            border: 2px dashed rgba(134,168,231,0.5);
            padding: 20px;
            text-align: center;
            border-radius: 16px;
            margin: 15px 0;
            background: rgba(15, 12, 41, 0.3);
        }
        .shop {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }
        .item {
            background: var(--glass);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(209,107,165,0.2);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .item:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(134,168,231,0.4);
            border-color: rgba(209,107,165,0.5);
        }
        .item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .item:hover img {
            transform: scale(1.05);
        }
        .item .info {
            padding: 20px;
        }
        .item h3 {
            font-size: 1.3rem;
            margin: 10px 0;
            color: white;
        }
        .item p {
            color: #c0c0ff;
            font-size: 0.95rem;
            margin: 10px 0;
            line-height: 1.5;
        }
        .price {
            font-weight: bold;
            font-size: 1.4rem;
            color: #ffffff;
            margin: 12px 0;
            text-shadow: 0 0 8px rgba(209,107,165,0.7);
        }
        .actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        .btn {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .view {
            background: rgba(134,168,231,0.2);
            border: 1px solid var(--neon-cyan);
            color: var(--neon-cyan);
        }
        .view:hover {
            background: rgba(134,168,231,0.4);
            box-shadow: 0 0 12px rgba(134,168,231,0.6);
        }
        .buy {
            background: rgba(74,222,128,0.2);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
        }
        .buy:hover {
            background: rgba(74,222,128,0.4);
            box-shadow: 0 0 12px rgba(74,222,128,0.6);
        }
        .delete {
            background: rgba(209,107,165,0.2);
            border: 1px solid var(--neon-purple);
            color: var(--neon-purple);
        }
        .delete:hover {
            background: rgba(209,107,165,0.4);
            box-shadow: 0 0 12px rgba(209,107,165,0.6);
        }
        .message {
            text-align: center;
            margin: 15px auto;
            padding: 12px 25px;
            max-width: 600px;
            border-radius: 50px;
            font-weight: bold;
            backdrop-filter: blur(8px);
            background: rgba(15, 12, 41, 0.6);
            border: 1px solid rgba(134,168,231,0.4);
            color: white;
        }
        .no-items {
            grid-column: 1 / -1;
            text-align: center;
            color: #a0a0ff;
            font-size: 1.2rem;
            padding: 40px;
        }
        @media (max-width: 600px) {
            .actions { flex-direction: column; }
            h1 { font-size: 2.0rem; }
        }
    </style>
</head>
<body>
 
<h1>✨ Etsy Clone</h1>
 
<?php if (isset($_GET['added'])): ?>
    <div class="message" style="border-color: rgba(134,168,231,0.7);">✅ Item added successfully!</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="message" style="border-color: rgba(209,107,165,0.7);">🗑️ Item deleted!</div>
<?php endif; ?>
 
<div class="form-box">
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Item Title" required>
        <textarea name="description" placeholder="Describe your item..." rows="3" required></textarea>
        <input type="number" step="0.01" name="price" placeholder="Price (e.g. 19.99)" min="0.01" required>
        <div class="upload-box">
            <p>📸 Upload Image</p>
            <input type="file" name="image" accept="image/*">
        </div>
        <p style="text-align:center; font-size:13px; color:#a0a0ff; margin:10px 0;">OR</p>
        <input type="url" name="image_url" placeholder="Image URL (optional)">
        <button type="submit">➕ Add Item</button>
    </form>
</div>
 
<div class="shop">
    <?php if (empty($listings)): ?>
        <div class="no-items">🌌 No items yet. Be the first seller!</div>
    <?php else: ?>
        <?php foreach ($listings as $item): ?>
            <div class="item">
                <?php if (!empty($item['image_url'])): ?>
                    <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                <?php else: ?>
                    <div style="height:200px;background:rgba(15,12,41,0.5);display:flex;align-items:center;justify-content:center;color:#a0a0ff;font-weight:bold;">No Image</div>
                <?php endif; ?>
                <div class="info">
                    <h3><?= htmlspecialchars($item['title']) ?></h3>
                    <p><?= htmlspecialchars(substr($item['description'], 0, 90)) ?>...</p>
                    <div class="price">$<?= number_format($item['price'], 2) ?></div>
                    <div class="actions">
                        <a href="index.php?view=<?= $item['id'] ?>" class="btn view">View</a>
                        <a href="index.php?buy=<?= $item['id'] ?>" class="btn buy">Buy</a>
                        <a href="index.php?delete=<?= $item['id'] ?>" class="btn delete" onclick="return confirm('Delete?')">Delete</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
 
</body>
</html>
