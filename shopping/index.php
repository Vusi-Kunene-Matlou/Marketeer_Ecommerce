<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Marketeer | Explore Everything</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body, html {
      height: 100%;
      font-family: 'Segoe UI', sans-serif;
    }

    .container-hero {
      display: flex;
      height: 100vh;
      width: 100vw;
      position: relative;
      overflow: hidden;
    }

    .bg-column {
      flex: 1;
      background-size: cover;
      background-position: center;
      animation: zoomPan 10s ease-in-out infinite alternate;
    }

    .bg1 { background-image: url('http://localhost/Marketeer_Ecommerce/shopping/img/gadgets.jpg'); }
    .bg2 { background-image: url('http://localhost/Marketeer_Ecommerce/shopping/img/electronics.jpg'); animation-delay: 1s; }
    .bg3 { background-image: url('http://localhost/Marketeer_Ecommerce/shopping/img/clothing.jpg'); animation-delay: 1s; }
    .bg4 { background-image: url('http://localhost/Marketeer_Ecommerce/shopping/img/furniture.jpg'); animation-delay: 1s; }

    @keyframes zoomPan {
      0% {
        transform: scale(1) translateY(0);
      }
      100% {
        transform: scale(1.1) translateY(-10px);
      }
    }

    .overlay-content {
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      background-color: rgba(0, 0, 0, 0.4);
      z-index: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
      flex-direction: column;
      padding: 20px;
    }

    .overlay-content h1 {
      font-size: 3rem;
      font-weight: bold;
      margin-bottom: 20px;
      animation: fadeIn 3s ease-in-out;
    }

    .overlay-content p {
      font-size: 1.2rem;
      animation: fadeIn 3s ease-in-out;
    }

    .shop-now-btn {
      font-size: 1.2rem;
      padding: 12px 28px;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 50px;
      transition: all 0.2s ease;
      text-decoration: none;
      margin-top: 20px;
      animation: fadeIn 5s ease-in-out;
    }

    .shop-now-btn:hover {
      background-color: #218838;
      transform: scale(1.05);
    }

    @keyframes fadeIn {
      0% { opacity: 0; transform: translateY(20px); }
      100% { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 768px) {
      .container-hero {
        flex-direction: column;
      }

      .bg-column {
        flex: none;
        height: 25%;
      }

      .overlay-content h1 {
        font-size: 2rem;
      }

      .overlay-content p {
        font-size: 1rem;
      }
    }
  </style>
</head>
<body>

  <div class="container-hero">
    <div class="bg-column bg1"></div>
    <div class="bg-column bg2"></div>
    <div class="bg-column bg3"></div>
    <div class="bg-column bg4"></div>

    <div class="overlay-content">
      <h1>Welcome to Marketeer</h1>
      <p class="mb-4">Shop gadgets, electronics, fashion, and furniture all in one place!</p>
      <a href="land_page_index.php" class="shop-now-btn">Shop Now</a>
    </div>
  </div>

</body>
</html>
