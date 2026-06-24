import choroplethMap from './choropleth.js'

document.addEventListener('alpine:init', () => {
    Alpine.data('choroplethMap', choroplethMap)
})
