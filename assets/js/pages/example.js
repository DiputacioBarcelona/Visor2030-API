/** Custom js for example page */

// Import Leaflet CSS
import 'leaflet/dist/leaflet.css';
// Import Leaflet JS
import L from 'leaflet';
// Import Chart.js
import Chart from 'chart.js/auto';

// Initialize the maps and chart
document.addEventListener('DOMContentLoaded', () => {
    // Initialize the maps
    let map1 = L.map('mapa1').setView([41.5, 2.3], 13);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: "Diputació de Barcelona"
    }).addTo(map1);

    let map2 = L.map('mapa2').setView([41.5, 2.3], 13);
    L.tileLayer('https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}&hl=ca', {
        subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
        attribution: "Diputació de Barcelona"
    }).addTo(map2);

    // Initialize the chart
    const ctx = document.getElementById('exemplegrafic');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Opció A', 'Opció B', 'Opció C', 'Opció D', 'Opció E', 'Opció F'],
            datasets: [{
                label: 'Nombre de Vots',
                data: [12, 19, 3, 5, 2, 3],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
