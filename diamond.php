<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Asha's SHOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="asha.png">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #0d0f14;
            color: #eaeaea;
        }

        .container {
            max-width: 900px;
            margin: 20px auto;
            background: #141824;
            padding: 24px;
            border-radius: 14px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, .6);
        }

        h1 {
            text-align: center;
            color: #fff;
            letter-spacing: 1px;
        }

        .subtitle {
            text-align: center;
            color: #9aa4c7;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section h2 {
            font-size: 17px;
            margin-bottom: 12px;
            color: #5ddcff;
            border-left: 4px solid #5ddcff;
            padding-left: 10px;
        }

        .item {
            display: flex;
            justify-content: space-between;
            padding: 11px 8px;
            border-bottom: 1px solid #242a3b;
            font-size: 15px;
        }

        .item span {
            font-weight: 600;
            color: #fff;
        }

        .price-note {
            text-align: center;
            font-size: 14px;
            color: #cfd6ff;
            margin-bottom: 14px;
        }

        .pay-box {
            background: linear-gradient(135deg, #1c2235, #151a2b);
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            font-size: 14px;
            color: #cfd6ff;
        }

        .copy-btn {
            margin-top: 10px;
            padding: 7px 16px;
            font-size: 13px;
            border: none;
            border-radius: 6px;
            background: linear-gradient(135deg, #ffb347, #ffcc33);
            color: #222;
            font-weight: 600;
            cursor: pointer;
        }

        .copy-btn:active {
            transform: scale(0.95);
        }

        .btn {
            display: block;
            width: auto;
            text-align: center;
            padding: 14px;
            margin-top: 14px;
            background: linear-gradient(135deg, #0088cc, #00c6ff);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn.admin {
            background: linear-gradient(135deg, #28a745, #57d163);
        }

        footer {
            text-align: center;
            color: #6f78a8;
            font-size: 13px;
            margin-top: 30px;
        }

        .alert {
            text-align: center;
        }

        /* Diamond Table Styles */
        #diamond-prices {
            font-family: Arial, sans-serif;
        }

        .category {
            margin-bottom: 20px;
        }

        .category h3 {
            color: #5ddcff;
            margin-bottom: 8px;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th, td {
            border: 1px solid #242a3b;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #1c1f2a;
            color: #5ddcff;
        }

        td {
            color: #fff;
        }

        @media (max-width: 600px) {
            .item {
                flex-direction: column;
                align-items: flex-start;
            }
            .item span {
                margin-top: 4px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Asha's SHOP</h1>
        <p class="subtitle">Fast & Reliable Digital Services</p>

        <!-- Diamond Prices Section -->
        <div id="diamond-prices" class="section">
            <h2>💎 MLBB Diamond Prices</h2>
            <div id="price-table">Loading prices...</div>
        </div>

        <div class="section">
            <h2>💳 Payment</h2>
            <div class="pay-box">
               Myo Thiha Ko <br> 📱 KBZ Pay / Wave Pay / AYA Pay <br> 📞 <span id="phone">09-769211585</span><br>
                <button class="copy-btn" onclick="copyPhone()">📋 Copy Phone</button>
            </div>
        </div>

        <p class="alert">Seller အစစ်ဆီမှာ Username နှစ်ခု ရှိပါတယ်။ အခြား ဘယ်အကောင့်မှ မသုံးပါ။</p>
        <a href="https://t.me/Ton_Star_Resell" class="btn">📩 Stars Resell Group</a>
        <a href="https://t.me/AshaRen20" class="btn admin">🔐 Admin</a>

        <footer>© 2025 Asha's Shop</footer>
    </div>

    <script>
        function copyPhone() {
            const phone = document.getElementById("phone").innerText;
            navigator.clipboard.writeText(phone).then(() => {
                alert("📞 Phone number copied!");
            });
        }

        // Diamond API
        const apiUrl = "https://z246014-c718oi.ls03.zwhhosting.com/api_diamonds.php";

        async function loadDiamondPrices() {
            try {
                const response = await fetch(apiUrl);
                const data = await response.json();

                const container = document.getElementById('price-table');
                container.innerHTML = '';

                for (const category in data) {
                    const catDiv = document.createElement('div');
                    catDiv.classList.add('category');

                    const title = document.createElement('h3');
                    title.textContent = category;
                    catDiv.appendChild(title);

                    const table = document.createElement('table');
                    const header = document.createElement('tr');
                    header.innerHTML = `<th>Name</th><th>Reseller Price</th><th>Seller Price</th>`;
                    table.appendChild(header);

                    data[category].forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td>${item.name}</td>
                                         <td>${item.reseller_price ? item.reseller_price.toLocaleString()+' MMK' : '-'}</td>
                                         <td>${item.seller_price ? item.seller_price.toLocaleString()+' MMK' : '-'}</td>`;
                        table.appendChild(row);
                    });

                    catDiv.appendChild(table);
                    container.appendChild(catDiv);
                }

            } catch (err) {
                console.error("Failed to load diamond prices:", err);
                document.getElementById('price-table').textContent = 'Failed to load prices';
            }
        }

        // Load prices on page load & auto-refresh every 60s
        window.addEventListener('DOMContentLoaded', () => {
            loadDiamondPrices();
            setInterval(loadDiamondPrices, 60000);
        });
    </script>
</body>

</html>
