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

const COLOR_NO_DATA_LIGHT = '#cbd5e1'
const COLOR_NO_DATA_DARK  = '#e5e7eb'

const GEOJSON_URLS = {
    kommun: '/geojson/kommuner.geojson',
    deso: '/geojson/deso.geojson',
}

// Property name used as the region key in each GeoJSON layer
const CODE_PROP = {
    kommun: 'id',
    deso: 'desokod',
}

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
        geojsonCache: {},
        scores: {},
        breaks: [],
        tooltip: null,
        popup: null,
        selectedCode: null,
        currentLevel: 'kommun',
        initialZoom: null,
        isZoomed: false,

        async init() {
            this.scores = JSON.parse(this.$el.dataset.mapData || '{}')
            this.currentLevel = this.$el.dataset.mapLevel || 'kommun'
            this.breaks = computeBreaks(this.scores, PALETTE.length)

            await this.loadGeojson(this.currentLevel)

            this.map = L.map(this.$refs.mapEl, {
                scrollWheelZoom: false,
                zoomControl: true,
                attributionControl: true,
            })

            this.drawLayer(this.currentLevel)
            this.map.fitBounds(this.layer.getBounds(), { padding: [4, 4] })
            this.initialZoom = this.map.getZoom()

            this.map.on('zoomend', () => {
                this.isZoomed = this.map.getZoom() > this.initialZoom
            })

            // Server dispatches map-updated after each filter change
            this.$wire.on('map-updated', async ({ mapData, level }) => {
                const levelChanged = level !== this.currentLevel

                this.scores = JSON.parse(mapData)
                this.breaks = computeBreaks(this.scores, PALETTE.length)

                if (levelChanged) {
                    this.selectedCode = null
                    this.popup?.remove()
                    this.popup = null
                    this.currentLevel = level
                    await this.loadGeojson(level)
                    this.layer?.remove()
                    this.drawLayer(level)
                    this.map.fitBounds(this.layer.getBounds(), { padding: [4, 4] })
                    this.initialZoom = this.map.getZoom()
                    this.isZoomed = false
                } else {
                    this.layer?.setStyle(f => this.styleFor(f, this.currentLevel))
                }
            })

            // Toplist row hover → highlight region on map (skip selected region reset)
            window.addEventListener('singelkartan:list-hover', e => {
                const code = e.detail?.code ?? null
                const prop = CODE_PROP[this.currentLevel]
                this.layer?.eachLayer(l => {
                    const lCode = l.feature?.properties?.[prop]
                    if (lCode === code) {
                        l.setStyle({ weight: 2, color: '#9f1239', fillOpacity: 0.95 })
                        l.bringToFront()
                    } else if (lCode !== this.selectedCode) {
                        this.layer.resetStyle(l)
                    }
                })
            })

            // Toplist row click → persistent highlight + popup + fly to bounds
            window.addEventListener('singelkartan:list-click', e => {
                const code = e.detail?.code ?? null
                if (code === this.selectedCode) {
                    this.clearSelection()
                    return
                }
                this.selectRegion(code)
            })

            // Popup closed by user (×) → clear selection
            this.map.on('popupclose', () => {
                if (this.selectedCode) this.clearSelection()
            })
        },

        resetView() {
            this.clearSelection()
            this.map.fitBounds(this.layer.getBounds(), { padding: [4, 4] })
        },

        selectRegion(code) {
            const prop = CODE_PROP[this.currentLevel]
            let targetLayer = null

            this.layer?.eachLayer(l => {
                if (l.feature?.properties?.[prop] === code) {
                    targetLayer = l
                    l.setStyle({ weight: 2.5, color: '#9f1239', fillOpacity: 0.95 })
                    l.bringToFront()
                } else {
                    this.layer.resetStyle(l)
                }
            })

            if (!targetLayer) return

            this.selectedCode = code
            const entry = this.scores[code]
            const feature = targetLayer.feature
            const name = this.currentLevel === 'deso'
                ? (feature.properties.desokod ?? code)
                : (feature.properties.kom_naam ?? feature.properties.kom_namn ?? code)

            const body = entry
                ? `${entry.pct}% singlar &nbsp;·&nbsp; ${entry.singles.toLocaleString('sv')} / ${entry.total.toLocaleString('sv')}`
                : 'Otillräckliga data'

            this.popup?.remove()
            this.popup = L.popup({ closeButton: true, className: 'singel-tip' })
                .setLatLng(targetLayer.getBounds().getCenter())
                .setContent(`<strong>${name}</strong><br><span>${body}</span>`)
                .openOn(this.map)

            this.map.flyToBounds(targetLayer.getBounds(), { maxZoom: 10, padding: [20, 20], duration: 0.6 })

            window.dispatchEvent(new CustomEvent('singelkartan:map-select', { detail: { code } }))
        },

        clearSelection() {
            const prop = CODE_PROP[this.currentLevel]
            this.layer?.eachLayer(l => {
                if (l.feature?.properties?.[prop] === this.selectedCode) {
                    this.layer.resetStyle(l)
                }
            })
            this.selectedCode = null
            this.popup?.remove()
            this.popup = null
            window.dispatchEvent(new CustomEvent('singelkartan:map-select', { detail: { code: null } }))
        },

        async loadGeojson(level) {
            if (this.geojsonCache[level]) return
            const res = await fetch(GEOJSON_URLS[level])
            this.geojsonCache[level] = await res.json()
        },

        isDark() {
            return document.documentElement.classList.contains('dark')
        },

        styleFor(feature, level) {
            const prop = CODE_PROP[level]
            const entry = this.scores[feature.properties[prop]]
            return {
                fillColor: entry ? scoreColor(entry.score, this.breaks) : (this.isDark() ? COLOR_NO_DATA_DARK : COLOR_NO_DATA_LIGHT),
                fillOpacity: this.isDark() ? 0.75 : 1,
                color: this.isDark() ? '#ffffff' : '#94a3b8',
                weight: level === 'deso' ? 0.3 : 0.5,
            }
        },

        drawLayer(level) {
            const geojson = this.geojsonCache[level]
            this.layer = L.geoJSON(geojson, {
                style: f => this.styleFor(f, level),
                onEachFeature: (feature, l) => {
                    l.on({
                        mouseover: e => this.onHover(e, feature, level),
                        mouseout:  e => this.onHoverOut(e),
                        click:     () => {
                            const prop = CODE_PROP[level]
                            const code = feature.properties[prop]
                            if (code === this.selectedCode) {
                                this.clearSelection()
                            } else {
                                this.selectRegion(code)
                            }
                        },
                    })
                },
            }).addTo(this.map)
        },

        onHover(e, feature, level) {
            const prop = CODE_PROP[level]
            const code  = feature.properties[prop]
            const name  = level === 'deso'
                ? (feature.properties.desokod ?? code)
                : (feature.properties.kom_namn ?? code)
            const entry = this.scores[code]

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
            const prop = CODE_PROP[this.currentLevel]
            const code = e.target.feature?.properties?.[prop]
            if (code !== this.selectedCode) {
                this.layer.resetStyle(e.target)
            }
            this.tooltip?.remove()
            this.tooltip = null
            window.dispatchEvent(new CustomEvent('singelkartan:map-hover', { detail: { code: null } }))
        },
    }
}
