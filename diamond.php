<?php
// diamond.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MLBB Diamond Prices | Asha's SHOP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial,sans-serif; background: #0d0f14; color:#fff; padding:20px;}
        h1 { text-align:center; color:#5ddcff; }
        .category { margin-bottom:30px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border:1px solid #242a3b; padding:8px; text-align:left; }
        th { background:#1c1f2a; color:#5ddcff; }
        td { color:#fff; }
    </style>
</head>
<body>
    <h1>💎 MLBB Diamond Prices</h1>
    <div id="diamond-prices">Loading prices...</div>

    <script>
        const apiUrl = "https://z246014-c718oi.ls03.zwhhosting.com/api_diamonds.php";

        async function loadDiamondPrices() {
            try {
                const res = await fetch(apiUrl);
                const data = await res.json();

                const container = document.getElementById('diamond-prices');
                container.innerHTML = '';

                for (const category in data) {
                    const catDiv = document.createElement('div');
                    catDiv.classList.add('category');

                    const title = document.createElement('h2');
                    title.textContent = category;
                    catDiv.appendChild(title);

                    const table = document.createElement('table');
                    const header = document.createElement('tr');
                    header.innerHTML = `<th>Name</th><th>Reseller Price</th><th>Seller Price</th>`;
                    table.appendChild(header);

                    data[category].forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td>${item.name}</td>
                                         <td>${item.reseller_price.toLocaleString()} MMK</td>
                                         <td>${item.seller_price.toLocaleString()} MMK</td>`;
                        table.appendChild(row);
                    });

                    catDiv.appendChild(table);
                    container.appendChild(catDiv);
                }

            } catch(err) {
                console.error("Failed to load diamond prices:", err);
                document.getElementById('diamond-prices').textContent = 'Failed to load prices';
            }
        }

        window.addEventListener('DOMContentLoaded', loadDiamondPrices);
    </script>
</body>
</html>
