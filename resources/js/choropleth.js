import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const PALETTE = [
    '#fce7f3', // pink-100
    '#fbcfe8', // pink-200
    '#f9a8d4', // pink-300
    '#f472b6', // pink-400
    '#ec4899', // pink-500
    '#db2777', // pink-600
]

const COLOR_NO_DATA = '#e5e7eb'

/**
 * Compute quantile break points from a scores map.
 * @param {Record<string, {score: number}>} scores
 * @param {number} numClasses
 * @returns {number[]}
 */
function computeBreaks(scores, numClasses) {
    const values = Object.values(scores)
        .map(e => e.score)
        .sort((a, b) => a - b)

    if (values.length === 0) return []

    const breaks = []
    for (let i = 1; i < numClasses; i++) {
        const idx = Math.floor((i / numClasses) * values.length)
        breaks.push(values[idx])
    }
    return breaks
}

function scoreColor(score, breaks) {
    for (let i = 0; i < breaks.length; i++) {
        if (score <= breaks[i]) return PALETTE[i]
    }
    return PALETTE[PALETTE.length - 1]
}

export default function choroplethMap() {
    return {
        map: null,
        layer: null,
        geojson: null,
        scores: {},
        breaks: [],
        tooltip: null,

        async init() {
            this.scores = JSON.parse(this.$el.dataset.mapData || '{}')
            this.breaks = computeBreaks(this.scores, PALETTE.length)

            const res = await fetch('/geojson/kommuner.geojson')
            this.geojson = await res.json()

            this.map = L.map(this.$refs.mapEl, {
                scrollWheelZoom: false,
                zoomControl: true,
                attributionControl: true,
            })

            this.drawLayer()
            this.map.fitBounds(this.layer.getBounds(), { padding: [4, 4] })

            // Livewire updates mapDataJson → redraw choropleth
            this.$watch('$wire.mapDataJson', value => {
                this.scores = JSON.parse(value)
                this.breaks = computeBreaks(this.scores, PALETTE.length)
                this.layer?.setStyle(f => this.styleFor(f))
            })

            // Toplist row hover → highlight region on map
            window.addEventListener('singelkartan:list-hover', e => {
                const code = e.detail?.code ?? null
                this.layer?.eachLayer(l => {
                    const lCode = l.feature?.properties?.id
                    if (lCode === code) {
                        l.setStyle({ weight: 2, color: '#9f1239', fillOpacity: 0.95 })
                        l.bringToFront()
                    } else {
                        this.layer.resetStyle(l)
                    }
                })
            })
        },

        styleFor(feature) {
            const entry = this.scores[feature.properties.id]
            return {
                fillColor: entry ? scoreColor(entry.score, this.breaks) : COLOR_NO_DATA,
                fillOpacity: 0.75,
                color: '#ffffff',
                weight: 0.5,
            }
        },

        drawLayer() {
            this.layer = L.geoJSON(this.geojson, {
                style: f => this.styleFor(f),
                onEachFeature: (feature, l) => {
                    l.on({
                        mouseover: e => this.onHover(e, feature),
                        mouseout:  e => this.onHoverOut(e),
                    })
                },
            }).addTo(this.map)
        },

        onHover(e, feature) {
            const code    = feature.properties.id
            const name    = feature.properties.kom_namn
            const entry   = this.scores[code]

            e.target.setStyle({ weight: 2, color: '#9f1239', fillOpacity: 0.95 })
            e.target.bringToFront()

            const body = entry
                ? `${entry.pct}% singlar &nbsp;·&nbsp; ${entry.singles.toLocaleString('sv')} / ${entry.total.toLocaleString('sv')}`
                : 'Otillräckliga data'

            if (!this.tooltip) {
                this.tooltip = L.tooltip({ sticky: true, className: 'singel-tip' })
            }
            this.tooltip
                .setContent(`<strong>${name}</strong><br><span>${body}</span>`)
                .setLatLng(e.latlng)
                .addTo(this.map)

            window.dispatchEvent(new CustomEvent('singelkartan:map-hover', { detail: { code } }))
        },

        onHoverOut(e) {
            this.layer.resetStyle(e.target)
            this.tooltip?.remove()
            this.tooltip = null
            window.dispatchEvent(new CustomEvent('singelkartan:map-hover', { detail: { code: null } }))
        },
    }
}
