/**
 * Leaflet.js Heatmap & Map Visualization Wrapper
 * 
 * Menggunakan library Leaflet.js (sudah ada di package.json) untuk:
 * - Heatmap distribusi geografis peneliti
 * - Marker institusi research
 * - Cluster marker untuk area padat
 * 
 * @package Wizdam SDG Analytics
 * @version 2.0
 */

// SDG Color System untuk peta
const SDG_COLORS = {
    'SDG1': '#E5243B', 'SDG2': '#DDA63A', 'SDG3': '#4C9F38', 'SDG4': '#C5192D',
    'SDG5': '#FF3A21', 'SDG6': '#26BDE2', 'SDG7': '#FCC30B', 'SDG8': '#A21942',
    'SDG9': '#FD6925', 'SDG10': '#DD1367', 'SDG11': '#FD9D24', 'SDG12': '#BF8B2E',
    'SDG13': '#3F7E44', 'SDG14': '#0A97D9', 'SDG15': '#56C02B', 'SDG16': '#00689D',
    'SDG17': '#19486A'
};

/**
 * LeafletMapWrapper Class
 * Mengelola inisialisasi dan manipulasi peta Leaflet
 */
class LeafletMapWrapper {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.map = null;
        this.markers = [];
        this.heatmapLayer = null;
        this.defaultOptions = {
            center: [-2.5489, 118.0149], // Indonesia center
            zoom: 5,
            minZoom: 3,
            maxZoom: 18,
            ...options
        };
    }

    /**
     * Initialize peta Leaflet
     */
    init() {
        if (!L || !document.getElementById(this.containerId)) {
            console.warn('Leaflet not loaded or container not found');
            return null;
        }

        this.map = L.map(this.containerId).setView(
            this.defaultOptions.center,
            this.defaultOptions.zoom
        );

        // Tambahkan tile layer (OpenStreetMap)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(this.map);

        return this.map;
    }

    /**
     * Tambahkan single marker
     */
    addMarker(lat, lng, popupContent = '', icon = null) {
        if (!this.map) return null;

        const marker = L.marker([lat, lng], { icon }).addTo(this.map);
        if (popupContent) {
            marker.bindPopup(popupContent);
        }
        this.markers.push(marker);
        return marker;
    }

    /**
     * Tambahkan multiple markers dari array data
     * Data format: [{lat, lng, popup, icon}, ...]
     */
    addMarkers(dataArray) {
        if (!Array.isArray(dataArray)) return [];

        return dataArray.map(item => {
            return this.addMarker(item.lat, item.lng, item.popup, item.icon);
        });
    }

    /**
     * Tambahkan circle marker dengan radius berdasarkan value
     */
    addCircleMarker(lat, lng, radius, color = '#3388ff', popupContent = '') {
        if (!this.map) return null;

        const circle = L.circleMarker([lat, lng], {
            radius: radius,
            fillColor: color,
            color: color,
            weight: 1,
            opacity: 1,
            fillOpacity: 0.6
        }).addTo(this.map);

        if (popupContent) {
            circle.bindPopup(popupContent);
        }

        this.markers.push(circle);
        return circle;
    }

    /**
     * Fit bounds untuk menampilkan semua markers
     */
    fitBounds() {
        if (!this.map || this.markers.length === 0) return;

        const group = new L.featureGroup(this.markers);
        this.map.fitBounds(group.getBounds().pad(0.1));
    }

    /**
     * Clear semua markers
     */
    clearMarkers() {
        this.markers.forEach(marker => {
            this.map.removeLayer(marker);
        });
        this.markers = [];
    }

    /**
     * Destroy map instance
     */
    destroy() {
        this.clearMarkers();
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    }
}

/**
 * Heatmap Visualization using Leaflet.heat plugin
 * Format data: [[lat, lng, intensity], ...]
 */
class HeatmapWrapper extends LeafletMapWrapper {
    constructor(containerId, options = {}) {
        super(containerId, options);
        this.heatData = [];
    }

    /**
     * Set heatmap data
     * @param {Array} data - Array of [lat, lng, intensity]
     */
    setData(data) {
        if (!Array.isArray(data)) {
            console.error('Heatmap data must be an array');
            return;
        }
        this.heatData = data;
    }

    /**
     * Render heatmap layer
     * @param {Object} options - Heatmap options (radius, blur, maxZoom, etc.)
     */
    render(options = {}) {
        if (!this.map || this.heatData.length === 0) {
            console.warn('Map not initialized or no heatmap data');
            return;
        }

        // Hapus heatmap lama jika ada
        if (this.heatmapLayer) {
            this.map.removeLayer(this.heatmapLayer);
        }

        // Default options
        const heatOptions = {
            radius: 25,
            blur: 15,
            maxZoom: 10,
            gradient: {
                0.4: 'blue',
                0.6: 'lime',
                0.7: 'yellow',
                0.8: 'orange',
                1.0: 'red'
            },
            ...options
        };

        // Buat heatmap layer (butuh leaflet-heat plugin)
        if (typeof L.heatLayer !== 'undefined') {
            this.heatmapLayer = L.heatLayer(this.heatData, heatOptions).addTo(this.map);
        } else {
            console.warn('leaflet-heat plugin not loaded. Using circle markers as fallback.');
            this.renderFallbackHeatmap();
        }

        return this.heatmapLayer;
    }

    /**
     * Fallback rendering jika leaflet-heat tidak tersedia
     * Menggunakan circle markers dengan opacity berdasarkan intensity
     */
    renderFallbackHeatmap() {
        this.heatData.forEach(point => {
            const [lat, lng, intensity] = point;
            const normalizedIntensity = Math.min(intensity / 100, 1);
            
            L.circleMarker([lat, lng], {
                radius: 10 + (normalizedIntensity * 20),
                fillColor: this.getColorForIntensity(normalizedIntensity),
                color: 'transparent',
                fillOpacity: normalizedIntensity * 0.7
            }).addTo(this.map);
        });
    }

    /**
     * Get color based on intensity (0-1)
     */
    getColorForIntensity(intensity) {
        if (intensity < 0.25) return '#0000ff'; // Blue
        if (intensity < 0.5) return '#00ff00';  // Green
        if (intensity < 0.75) return '#ffff00'; // Yellow
        if (intensity < 0.9) return '#ffa500';  // Orange
        return '#ff0000'; // Red
    }

    /**
     * Update heatmap data dan re-render
     */
    updateData(newData, options = {}) {
        this.setData(newData);
        this.render(options);
    }
}

/**
 * Institutional Cluster Map
 * Khusus untuk menampilkan cluster institusi penelitian
 */
class InstitutionClusterMap extends LeafletMapWrapper {
    constructor(containerId, options = {}) {
        super(containerId, options);
        this.markerClusterGroup = null;
    }

    /**
     * Initialize dengan MarkerCluster support
     */
    initWithClusters() {
        this.init();

        if (!this.map) return null;

        // Cek apakah Leaflet.markercluster tersedia
        if (typeof L.markerClusterGroup !== 'undefined') {
            this.markerClusterGroup = L.markerClusterGroup({
                showCoverageOnHover: true,
                maxClusterRadius: 50,
                spiderfyOnMaxZoom: true,
                removeOutsideVisibleBounds: true
            });
            this.map.addLayer(this.markerClusterGroup);
        }

        return this.map;
    }

    /**
     * Add institutions dengan automatic clustering
     * Data format: [{name, lat, lng, count, sdgs}, ...]
     */
    addInstitutions(institutionsData) {
        if (!this.map || !Array.isArray(institutionsData)) return;

        institutionsData.forEach(inst => {
            const popupContent = `
                <div class="institution-popup">
                    <h4>${inst.name}</h4>
                    <p><strong>Researchers:</strong> ${inst.count || 0}</p>
                    ${inst.sdgs ? `<p><strong>Top SDGs:</strong> ${inst.sdgs.join(', ')}</p>` : ''}
                </div>
            `;

            const marker = L.marker([inst.lat, inst.lng]);
            marker.bindPopup(popupContent);

            if (this.markerClusterGroup) {
                this.markerClusterGroup.addLayer(marker);
            } else {
                marker.addTo(this.map);
                this.markers.push(marker);
            }
        });
    }
}

/**
 * Helper function untuk geocoding institusi Indonesia
 * Returns approximate coordinates untuk institusi besar
 */
function getInstitutionCoordinates(institutionName) {
    const knownInstitutions = {
        'universitas indonesia': [-6.3651, 106.8242], // Depok
        'institut teknologi bandung': [-6.8915, 107.6107], // Bandung
        'universitas gadjah mada': [-7.7688, 110.3735], // Yogyakarta
        'institut pertanian bogor': [-6.5971, 106.7972], // Bogor
        'universitas airlangga': [-7.2663, 112.7977], // Surabaya
        'universitas diponegoro': [-7.0522, 110.4386], // Semarang
        'universitas hasanuddin': [-5.1477, 119.4327], // Makassar
        'universitas sumatera utara': [3.5633, 98.6722], // Medan
        'universitas brawijaya': [-7.9666, 112.6326], // Malang
        'universitas sebelas maret': [-7.5615, 110.8315] // Surakarta
    };

    const normalizedName = institutionName.toLowerCase();
    
    for (const [key, coords] of Object.entries(knownInstitutions)) {
        if (normalizedName.includes(key)) {
            return coords;
        }
    }

    // Default: Jakarta center jika tidak ditemukan
    return [-6.2088, 106.8456];
}

/**
 * Create map visualization dari database researchers
 * Dipanggil dari PHP backend
 */
function createResearcherMap(containerId, researchersData) {
    const mapWrapper = new LeafletMapWrapper(containerId);
    mapWrapper.init();

    if (!researchersData || researchersData.length === 0) {
        console.log('No researcher data for map');
        return mapWrapper;
    }

    // Tambahkan markers untuk setiap researcher dengan institusi
    researchersData.forEach(researcher => {
        if (researcher.institutions && Array.isArray(researcher.institutions)) {
            researcher.institutions.forEach(inst => {
                const coords = getInstitutionCoordinates(inst);
                const popup = `
                    <div class="researcher-popup">
                        <h4>${researcher.name}</h4>
                        <p><strong>Institution:</strong> ${inst}</p>
                        <p><strong>Works:</strong> ${researcher.total_works || 0}</p>
                        <a href="/orcid/${researcher.orcid}" class="btn btn-sm">View Profile</a>
                    </div>
                `;
                mapWrapper.addMarker(coords[0], coords[1], popup);
            });
        }
    });

    mapWrapper.fitBounds();
    return mapWrapper;
}

// Export untuk module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        LeafletMapWrapper,
        HeatmapWrapper,
        InstitutionClusterMap,
        createResearcherMap,
        getInstitutionCoordinates,
        SDG_COLORS
    };
}
