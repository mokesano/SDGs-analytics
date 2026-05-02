/**
 * D3.js Network & Chord Diagram Visualization Wrapper
 * 
 * Menggunakan library D3.js v7 (sudah ada di package.json) untuk:
 * - Network graph peneliti ↔ SDG
 * - Chord diagram relasi antar SDG
 * - Force-directed graphs
 * - Interactive node-link diagrams
 * 
 * @package Wizdam SDG Analytics
 * @version 2.0
 */

// SDG Color System untuk D3
const SDG_D3_COLORS = [
    '#E5243B', '#DDA63A', '#4C9F38', '#C5192D', '#FF3A21',
    '#26BDE2', '#FCC30B', '#A21942', '#FD6925', '#DD1367',
    '#FD9D24', '#BF8B2E', '#3F7E44', '#0A97D9', '#56C02B',
    '#00689D', '#19486A'
];

/**
 * SDGNetworkGraph Class
 * Membuat network graph interaktif peneliti ↔ SDG connections
 */
class SDGNetworkGraph {
    constructor(containerId, width = 800, height = 600) {
        this.containerId = containerId;
        this.width = width;
        this.height = height;
        this.svg = null;
        this.simulation = null;
        this.nodes = [];
        this.links = [];
    }

    /**
     * Initialize SVG dan setup dasar
     */
    init() {
        const container = d3.select(`#${this.containerId}`);
        if (container.empty()) {
            console.error(`Container #${this.containerId} not found`);
            return null;
        }

        // Clear container
        container.html('');

        // Create SVG
        this.svg = container.append('svg')
            .attr('width', this.width)
            .attr('height', this.height)
            .attr('viewBox', [0, 0, this.width, this.height])
            .attr('style', 'max-width: 100%; height: auto; font: 12px sans-serif;');

        // Define arrow markers
        this.svg.append('defs').selectAll('marker')
            .data(['end'])
            .join('marker')
            .attr('id', 'arrow')
            .attr('viewBox', '0 -5 10 10')
            .attr('refX', 25)
            .attr('refY', 0)
            .attr('markerWidth', 6)
            .attr('markerHeight', 6)
            .attr('orient', 'auto')
            .append('path')
            .attr('fill', '#999')
            .attr('d', 'M0,-5L10,0L0,5');

        return this.svg;
    }

    /**
     * Set data untuk network graph
     * @param {Object} data - {nodes: [], links: []}
     * Node format: {id, type: 'researcher'|'sdg', group, value}
     * Link format: {source, target, value}
     */
    setData(data) {
        if (!data || !data.nodes || !data.links) {
            console.error('Invalid network data format');
            return;
        }
        this.nodes = data.nodes;
        this.links = data.links;
    }

    /**
     * Render force-directed network graph
     */
    render() {
        if (!this.svg || this.nodes.length === 0) {
            console.warn('SVG not initialized or no nodes');
            return;
        }

        // Create force simulation
        this.simulation = d3.forceSimulation(this.nodes)
            .force('link', d3.forceLink(this.links).id(d => d.id).distance(100))
            .force('charge', d3.forceManyBody().strength(-300))
            .force('center', d3.forceCenter(this.width / 2, this.height / 2))
            .force('collide', d3.forceCollide().radius(30));

        // Draw links
        const link = this.svg.append('g')
            .attr('stroke', '#999')
            .attr('stroke-opacity', 0.6)
            .selectAll('line')
            .data(this.links)
            .join('line')
            .attr('stroke-width', d => Math.sqrt(d.value) * 2);

        // Draw nodes
        const node = this.svg.append('g')
            .attr('stroke', '#fff')
            .attr('stroke-width', 1.5)
            .selectAll('circle')
            .data(this.nodes)
            .join('circle')
            .attr('r', d => d.type === 'sdg' ? 20 : 10)
            .attr('fill', d => {
                if (d.type === 'sdg') {
                    const sdgIndex = parseInt(d.id.replace('SDG', '')) - 1;
                    return SDG_D3_COLORS[sdgIndex] || '#999';
                }
                return '#69b3a2';
            })
            .call(this.drag(this.simulation));

        // Add tooltips
        node.append('title')
            .text(d => `${d.id}: ${d.value || 0} connections`);

        // Add labels for SDG nodes
        const labels = this.svg.append('g')
            .attr('text-anchor', 'middle')
            .attr('fill', '#333')
            .selectAll('text')
            .data(this.nodes.filter(d => d.type === 'sdg'))
            .join('text')
            .text(d => d.id)
            .attr('font-size', '10px')
            .attr('dy', 35);

        // Update positions on tick
        this.simulation.on('tick', () => {
            link
                .attr('x1', d => d.source.x)
                .attr('y1', d => d.source.y)
                .attr('x2', d => d.target.x)
                .attr('y2', d => d.target.y);

            node
                .attr('cx', d => d.x)
                .attr('cy', d => d.y);

            labels
                .attr('x', d => d.x)
                .attr('y', d => d.y);
        });

        // Add zoom behavior
        const zoom = d3.zoom()
            .scaleExtent([0.1, 4])
            .on('zoom', (event) => {
                this.svg.selectAll('g').attr('transform', event.transform);
            });

        this.svg.call(zoom);

        return { simulation: this.simulation, node, link };
    }

    /**
     * Drag behavior for nodes
     */
    drag(simulation) {
        function dragstarted(event) {
            if (!event.active) simulation.alphaTarget(0.3).restart();
            event.subject.fx = event.subject.x;
            event.subject.fy = event.subject.y;
        }

        function dragged(event) {
            event.subject.fx = event.x;
            event.subject.fy = event.y;
        }

        function dragended(event) {
            if (!event.active) simulation.alphaTarget(0);
            event.subject.fx = null;
            event.subject.fy = null;
        }

        return d3.drag()
            .on('start', dragstarted)
            .on('drag', dragged)
            .on('end', dragended);
    }

    /**
     * Update data dan restart simulation
     */
    updateData(newData) {
        this.setData(newData);
        if (this.simulation) {
            this.simulation.nodes(newData.nodes);
            this.simulation.force('link').links(newData.links);
            this.simulation.alpha(1).restart();
        } else {
            this.render();
        }
    }

    /**
     * Destroy graph dan cleanup
     */
    destroy() {
        if (this.simulation) {
            this.simulation.stop();
        }
        if (this.svg) {
            this.svg.remove();
        }
        this.nodes = [];
        this.links = [];
    }
}

/**
 * SDGChordDiagram Class
 * Membuat chord diagram untuk visualisasi relasi antar SDG
 */
class SDGChordDiagram {
    constructor(containerId, size = 600) {
        this.containerId = containerId;
        this.size = size;
        this.outerRadius = size / 2 - 20;
        this.innerRadius = size / 2 - 100;
        this.svg = null;
        this.chord = null;
    }

    /**
     * Initialize SVG
     */
    init() {
        const container = d3.select(`#${this.containerId}`);
        if (container.empty()) {
            console.error(`Container #${this.containerId} not found`);
            return null;
        }

        container.html('');

        this.svg = container.append('svg')
            .attr('width', this.size)
            .attr('height', this.size)
            .attr('viewBox', [-this.size / 2, -this.size / 2, this.size, this.size])
            .attr('style', 'max-width: 100%; height: auto; font: 10px sans-serif;');

        return this.svg;
    }

    /**
     * Set matrix data untuk chord diagram
     * Matrix format: 2D array NxN dimana N = jumlah SDG
     */
    setMatrix(matrix) {
        if (!Array.isArray(matrix) || matrix.length === 0) {
            console.error('Invalid matrix data');
            return;
        }
        
        this.chord = d3.chord()
            .padAngle(0.05)
            .sortSubgroups(d3.descending)(matrix);
    }

    /**
     * Render chord diagram
     */
    render() {
        if (!this.svg || !this.chord) {
            console.warn('SVG not initialized or no matrix data');
            return;
        }

        const g = this.svg.append('g');

        // Draw ribbons (connections)
        g.append('g')
            .selectAll('path')
            .data(this.chord.ribbons)
            .join('path')
            .attr('fill', d => SDG_D3_COLORS[d.source.index])
            .attr('fill-opacity', 0.7)
            .attr('stroke', '#fff')
            .attr('d', d3.ribbon().radius(this.innerRadius))
            .append('title')
            .text(d => `SDG${d.source.index + 1} → SDG${d.target.index + 1}: ${d.source.value}`);

        // Draw groups (SDG segments)
        const group = g.append('g')
            .selectAll('g')
            .data(this.chord.groups)
            .join('g');

        // Group arcs
        group.append('path')
            .attr('fill', d => SDG_D3_COLORS[d.index])
            .attr('stroke', '#fff')
            .attr('d', d3.arc()
                .innerRadius(this.innerRadius)
                .outerRadius(this.outerRadius)
            )
            .append('title')
            .text(d => `SDG${d.index + 1}: ${d.value} connections`);

        // Group labels
        group.append('text')
            .each(function(d) {
                d.angle = (d.startAngle + d.endAngle) / 2;
            })
            .attr('dy', '.35em')
            .attr('transform', d => `
                rotate(${(d.angle * 180 / Math.PI) - 90})
                translate(${this.outerRadius + 20})
                ${d.angle > Math.PI ? 'rotate(180)' : ''}
            `)
            .attr('text-anchor', d => d.angle > Math.PI ? 'end' : 'start')
            .text(d => `SDG${d.index + 1}`)
            .clone(true)
            .lower()
            .attr('stroke', '#fff');

        return g;
    }

    /**
     * Update matrix dan re-render
     */
    updateMatrix(newMatrix) {
        this.setMatrix(newMatrix);
        if (this.svg) {
            this.svg.selectAll('*').remove();
            this.render();
        }
    }

    /**
     * Destroy diagram
     */
    destroy() {
        if (this.svg) {
            this.svg.remove();
        }
    }
}

/**
 * Helper: Create network data dari database researchers & works
 */
function createNetworkDataFromDB(researchers, workSdgs) {
    const nodes = [];
    const links = [];
    const sdgCounts = {};

    // Tambahkan SDG nodes
    for (let i = 1; i <= 17; i++) {
        nodes.push({
            id: `SDG${i}`,
            type: 'sdg',
            group: i,
            value: 0
        });
        sdgCounts[`SDG${i}`] = 0;
    }

    // Tambahkan researcher nodes dan links
    researchers.forEach((r, idx) => {
        nodes.push({
            id: r.orcid || `researcher_${idx}`,
            type: 'researcher',
            group: 18,
            value: r.total_works || 1
        });

        // Buat links ke SDG berdasarkan work_sdgs
        if (workSdgs && workSdgs[r.orcid]) {
            Object.entries(workSdgs[r.orcid]).forEach(([sdg, count]) => {
                links.push({
                    source: r.orcid || `researcher_${idx}`,
                    target: sdg,
                    value: count
                });
                sdgCounts[sdg] += count;
            });
        }
    });

    // Update SDG node values
    nodes.filter(n => n.type === 'sdg').forEach(node => {
        node.value = sdgCounts[node.id];
    });

    return { nodes, links };
}

/**
 * Helper: Create matrix data untuk chord diagram dari co-occurrence SDG
 */
function createCooccurrenceMatrix(workSdgs) {
    const size = 17;
    const matrix = Array(size).fill(null).map(() => Array(size).fill(0));

    // Hitung co-occurrence
    Object.values(workSdgs).forEach(sdgs => {
        const presentSdgs = Object.keys(sdgs).map(sdg => 
            parseInt(sdg.replace('SDG', '')) - 1
        );

        presentSdgs.forEach(i => {
            presentSdgs.forEach(j => {
                if (i !== j) {
                    matrix[i][j]++;
                }
            });
        });
    });

    return matrix;
}

// Export untuk module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        SDGNetworkGraph,
        SDGChordDiagram,
        createNetworkDataFromDB,
        createCooccurrenceMatrix,
        SDG_D3_COLORS
    };
}
