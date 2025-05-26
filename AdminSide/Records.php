<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RestEase Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/Analytics.css">
  <link rel="stylesheet" href="../css/sidebar.css">
  <style>
    .cemetery-masterlist-container {
      margin-left: 50px;
      margin-top: 30px;
      padding: 0 32px;
      font-family: 'Inter', sans-serif;
    }
    .cemetery-masterlist-title {
      font-size: 2rem;
      font-weight: 600;
      margin-bottom: 0.25rem;
    }
    .cemetery-masterlist-desc {
      font-size: 1rem;
      color: #555;
      margin-bottom: 1.5rem;
    }
    .cemetery-masterlist-controls {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
      flex-wrap: wrap;
      gap: 10px;
    }
    .search-container {
      flex: 1 1 320px;
      max-width: 420px;
      display: flex;
      align-items: center;
      background: #fff;
      border-radius: 10px;
      border: 1.5px solid #bfc8d2; /* Faded, lighter border */
      padding: 0 16px;
      height: 40px;
      min-width: 320px;
      box-shadow: 0 1px 4px rgba(60,72,88,0.03);
    }
    .search-container i {
      color: #b0b0b0;
      margin-right: 8px;
      font-size: 1.1rem;
    }
    .search-container input {
      border: none;
      background: transparent;
      outline: none;
      font-size: 1.04rem;
      width: 100%;
      color: #222;
      font-weight: 400;
      padding: 0;
      margin: 0;
    }
    .search-container input::placeholder {
      color: #b0b0b0;
      font-weight: 400;
      opacity: 1;
    }
    .cemetery-masterlist-actions button {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 7px 18px;
      font-size: 1rem;
      margin-left: 8px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: background 0.2s;
    }
    .cemetery-masterlist-actions button:hover {
      background: #f2f2f2;
    }
    .cemetery-masterlist-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 1px 4px rgba(0,0,0,0.04);
      margin-bottom: 1rem;
    }
    .cemetery-masterlist-table th, .cemetery-masterlist-table td {
      padding: 10px 12px;
      text-align: left;
      font-size: 0.98rem;
      border-bottom: 1px solid #eee;
      background: #fff;
    }
    .cemetery-masterlist-table th {
      background: #f7f8fa;
      font-weight: 500;
      color: #333;
    }
    .cemetery-masterlist-table tr:last-child td {
      border-bottom: none;
    }
    .cemetery-masterlist-pagination {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 1rem;
      margin-bottom: 1rem;
    }
    .cemetery-masterlist-pagination button {
      border: 1px solid #ddd;
      background: #fff;
      border-radius: 6px;
      width: 32px;
      height: 32px;
      font-size: 1rem;
      cursor: pointer;
      color: #506C84;
      transition: background 0.2s;
    }
    .cemetery-masterlist-pagination button.active,
    .cemetery-masterlist-pagination button:focus {
      background: #506C84;
      color: #fff;
      border-color: #506C84;
    }
    .cemetery-masterlist-pagination button:disabled {
      color: #bbb;
      border-color: #eee;
      background: #fafbfc;
      cursor: not-allowed;
    }
    @media (max-width: 900px) {
      .cemetery-masterlist-container {
        margin-left: 0;
        padding: 0 10px;
      }
      .cemetery-masterlist-controls {
        flex-direction: column;
        align-items: stretch;
      }
      .cemetery-masterlist-search {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <!-- Sidebar -->
  <?php include '../Includes/sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main-content">
    
    <!-- Cemetery Masterlist Section -->
    <div class="cemetery-masterlist-container">
      <div style="display: flex; align-items: center; justify-content: space-between;">
        <div class="cemetery-masterlist-title">Cemetery Masterlist</div>
        <div class="user-profile" style="display: flex; align-items: center;">
          <div class="notification-icon">
            <i class="fas fa-bell"></i>
            <span class="notification-badge">1</span>
          </div>
          <div class="profile-info" style="margin-left: 10px;">
            <img src="../assets/Default Image.jpg" alt="Profile" class="profile-avatar">
            <div>
              <div class="profile-name">Sybau</div>
              <div class="profile-role">Admin</div>
            </div>
          </div>
        </div>
      </div>
      <div class="cemetery-masterlist-desc">View all Records Information.</div>
      <div class="cemetery-masterlist-controls">
         <div class="search-container">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search">
      </div>
        <div class="cemetery-masterlist-actions">
          <button><i class="fas fa-file-export"></i> Export</button>
          <button><i class="fas fa-filter"></i> Filter</button>
        </div>
      </div>
      <div style="overflow-x:auto;">
        <table class="cemetery-masterlist-table">
          <thead>
            <tr>
              <th>Apt No.</th>
              <th>Name of Deceases</th>
              <th>Address of Deceased</th>
              <th>Informant Name</th>
              <th>Date Died</th>
              <th>Date Internment</th>
              <th>Vadlity</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>1F-0A1</td>
              <td>Calzoni, Omar</td>
              <td>Poblacion</td>
              <td>Rayna Dias</td>
              <td>03-14-09</td>
              <td>03-19-09</td>
              <td>03-19-09</td>
            </tr>
            <tr>
              <td>1F-0A2</td>
              <td>Dokidis, Kaiya</td>
              <td>Poblacion</td>
              <td>Wilson Aminoff</td>
              <td>07-22-15</td>
              <td>07-27-15</td>
              <td>07-27-15</td>
            </tr>
            <tr>
              <td>1F-0A3</td>
              <td>Donin, Lincoln</td>
              <td>Poblacion</td>
              <td>Brandon Saris</td>
              <td>11-05-02</td>
              <td>11-10-02</td>
              <td>11-10-02</td>
            </tr>
            <tr>
              <td>1F-0A4</td>
              <td>Geidt, Kaylynn</td>
              <td>Poblacion</td>
              <td>Zain Philips</td>
              <td>08-17-19</td>
              <td>08-22-19</td>
              <td>08-22-19</td>
            </tr>
            <tr>
              <td>1F-0A5</td>
              <td>Herwitz, Ahmad</td>
              <td>Poblacion</td>
              <td>Wilson Lubin</td>
              <td>01-09-07</td>
              <td>01-16-07</td>
              <td>01-16-07</td>
            </tr>
            <tr>
              <td>1F-0A6</td>
              <td>Press, Gustavo</td>
              <td>Poblacion</td>
              <td>Wilson Culhane</td>
              <td>04-28-13</td>
              <td>04-24-13</td>
              <td>04-24-13</td>
            </tr>
            <tr>
              <td>1F-0A7</td>
              <td>Schleifer, Tiana</td>
              <td>Poblacion</td>
              <td>Adison Vetrovs</td>
              <td>10-31-04</td>
              <td>10-36-04</td>
              <td>10-36-04</td>
            </tr>
            <tr>
              <td>1F-0A8</td>
              <td>Siphorn, Jordyn</td>
              <td>Poblacion</td>
              <td>Jocelyn Mango</td>
              <td>06-03-21</td>
              <td>06-07-21</td>
              <td>06-07-21</td>
            </tr>
            <tr>
              <td>1F-0A9</td>
              <td>Torff, Skylar</td>
              <td>Poblacion</td>
              <td>Craig Workman</td>
              <td>12-11-06</td>
              <td>12-28-06</td>
              <td>12-28-06</td>
            </tr>
            <tr>
              <td>1F-0A10</td>
              <td>Westervelt, Haylie</td>
              <td>Poblacion</td>
              <td>Jakob Bator</td>
              <td>09-25-18</td>
              <td>09-30-18</td>
              <td>09-30-18</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="cemetery-masterlist-pagination" style="justify-content: center;">
        <button disabled>&lt;</button>
        <button>1</button>
        <button class="active">2</button>
        <button>3</button>
        <button>&gt;</button>
      </div>

      <div>
        <span>Page 1 of 3</span>
      </div>
    </div>
  </main>
</body>
</html>
