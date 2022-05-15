<template>
  <div class="app-dashboard">
    <section class="w-full text-center">
      In order to use this plugin, your current site must be hosted on www
      or you use a DNS service that support Alias record:

      Step 1 - create zone
      ApiKey - "enter your api key"
      Origin Url:    https://IP-Address
      Canonical Url: https://www.google.com
      Name: www-google-com

      <button>Create Zone</button>

      Step 2 - zone found, setup DNS

      Now that you have the zone, you need to go configure DNS
      example.com     A     IP
      www.example.com CNAME www-www-www.b-cdn.net

      if your provider has alias record, then setup
      example.com ALIAS www-www-www.b-cdn.net

      otherwise, you can setup example.com to redirect to wwww.example.com
      with .htaccess

      Step 3 - Issue SSL and apply EdgeRules
      You must complete the DNS configuration above in order to issue SSL.

      if DNS match cname, enable issue SSL button, otherwise disable
      <button>issue ssl for example.com</button>
      <button>issue ssl for www.example.com</button>

      checkbox:
      [x] Browser Cache Static Contents
      [x] Always ByPass Cache
      [x] Bunnyflare Canonical SSL Redirect
      <button>Apply Edge Rules</button>
    </section>
  </div>
</template>

<script>
import { defineComponent, reactive, ref, nextTick, toRaw } from 'vue'

// API documentation here
// https://docs.bunny.net/reference/bunnynet-api-overview

export default defineComponent({
  components: {},
  name: 'Dashboard',
  setup () {
    const settings = reactive({
      originDomain: '',
      canonicalSlug: '',
      canonicalDomain: ''
    })
    const headers = reactive({
      Accept: 'application/json',
      AccessKey: ''
    })
    const hasLoaded = ref(false)
    const rules = [
      {
        Triggers: [
          {
            PatternMatches: ['*b-cdn.net/*', 'http://*', '*://example.com/*'],
            Type: 0,
            PatternMatchingType: 0
          }
        ],
        ActionType: 1,
        ActionParameter1: 'https://www.example.com{{path}}',
        Enabled: true,
        TriggerMatchingType: 0,
        Description: 'Bunnyflare Canonical SSL Redirect'
      },
      {
        Triggers: [
          {
            PatternMatches: ['*wp-postpass*', '*wordpress_logged_in*', '*woocommerce_*', '*_cart*'],
            Type: 1,
            PatternMatchingType: 0,
            Parameter1: 'cookie'
          },
          {
            PatternMatches: ['unapproved*', 's=*', 'preview=*'],
            Type: 6,
            PatternMatchingType: 0
          },
          {
            PatternMatches: ['*/wp-admin*', '*/wp-json*', '*/page/*', '*/ajax*', '*.php*'],
            Type: 0,
            PatternMatchingType: 0
          }
        ],
        ActionType: 3,
        ActionParameter1: '0',
        Enabled: true,
        TriggerMatchingType: 0,
        Description: 'Bunnyflare Always ByPass Cache'
      },
      {
        Triggers: [
          {
            PatternMatches: ['css', 'j*', 'mp*', 'pdf', 'do*'],
            Type: 3,
            PatternMatchingType: 0
          },
          {
            PatternMatches: ['*image*', '*font*'],
            Type: 2,
            PatternMatchingType: 0,
            Parameter1: 'content-type'
          },
          { PatternMatches: ['*/wp-content/uploads* '], Type: 0, PatternMatchingType: 0}
        ],
        ActionType: 16,
        ActionParameter1: '31536000',
        Enabled: true,
        TriggerMatchingType: 0,
        Description: 'Bunnyflare Browser Cache Static Contents'
      }
    ]

    return {
      settings,
      hasLoaded,
      headers
    }
  },
  methods: {
    async createZone() {
      try {
        const data = {
          url: `https://api.bunny.net/pullzone`,
          headers: toRaw(this.headers),
          data: {
            Name: this.canonicalSlug,
            OriginUrl: `https://${this.originDomain}`,
            Type: 0
          }
        }

        // server-side handle post
        const rst = await this.axios.post(this.endpoints.createPullZone, data)

        // const rst = { success: true }
        if (rst.status == 200) {
          return JSON.parse(rst.data)
        }
      } catch (err) {
        this.$swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: 'Server response with error.',
          footer: '<div class="overflow-footer w-full">' + err.message + '</div>'
        })
      }
    },
    async addHostname($zoneId) {
      try {
        const url = `https://api.bunny.net/pullzone/${zoneId}/addHostname`
        const data = {
          { Hostname: this.canonicalDomain }
        }
        const headers = toRaw(this.headers)

        // server-side handle post
        const rst = await this.axios.post(this.endpoints.createPullZone, data)

        // const rst = { success: true }
        if (rst.status == 200) {
          return JSON.parse(rst.data)
        }
      } catch (err) {
        this.$swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: 'Server response with error.',
          footer: '<div class="overflow-footer w-full">' + err.message + '</div>'
        })
      }
    },
    async updateZone(zoneId) {
      try {
        const url = `https://api.bunny.net/pullzone/${zoneId}`
        const data = {
          CacheControlMaxAgeOverride: 8400,
          AddHostHeader: true,
          EnableQueryStringOrdering: true
        }
        const headers = toRaw(this.headers)

        // server-side handle post
        const rst = await this.axios.post(this.endpoints.createPullZone, data)

        // const rst = { success: true }
        if (rst.status == 200) {
          return JSON.parse(rst.data)
        }
      } catch (err) {
        this.$swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: 'Server response with error.',
          footer: '<div class="overflow-footer w-full">' + err.message + '</div>'
        })
      }
    },
    async createRule(zoneId, data) {
      try {
        const url = `https://api.bunny.net/pullzone/${zoneId}/edgerules/addOrUpdate`
        const headers = toRaw(this.headers)

        // server-side handle post
        const rst = await this.axios.post(this.endpoints.createPullZone, data)

        // const rst = { success: true }
        if (rst.status == 200) {
          return JSON.parse(rst.data)
        }
      } catch (err) {
        this.$swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: 'Server response with error.',
          footer: '<div class="overflow-footer w-full">' + err.message + '</div>'
        })
      }
    },
    async doSetup() {
      const zones = listZones()
      let zone = zones.find(x => x.Name === canonicalSlug)
      if (undefined === zone) {
        zone = await this.createZone()
      }

      await this.updateZone(zone.Id)
      await this.createRule(zone.Id, this.params[0])
      await this.createRule(zone.Id, this.params[1])
      await this.createRule(zone.Id, this.params[2])
    },
    async listZones() {
      try {
        let res = await this.axios({
            url: 'https://api.bunny.net/pullzone',
            method: 'GET',
            timeout: 8000,
            headers: toRaw(this.headers)
        })

        if(res.status == 200){
          return JSON.parse(res.data)
        }
      }
      catch (err) {
        console.error(err);
      }

      return []
    },
    async doLoad() {
      await nextTick()

      // @ts-ignore
      const config = this.$win.vue_wp_plugin_config_admin

      // @ts-ignore
      if (! this.$win.$appConfig.nonce) {
        this.$win.$appConfig.nonce = config.rest.nonce
      }

      this.settings.zoneId = config.zoneId
      this.settings.originDomain = config.originDomain
      this.settings.canonicalDomain = `www.${config.originDomain}`
      this.settings.canonicalSlug = this.slugify(config.originDomain)
      this.headers = {
        Accept: 'application/json',
        AccessKey: config.accessKey
      }

      // make sure data is loaded before ui render
      this.hasLoaded = true
      this.$forceUpdate()
    },
    slugify(str) {
      return `${str}`.toLowerCase().replace(/[^0-9a-z-]+/g,'-').replace(/-+/g, '-')
    }
  },
  beforeMount() {
    var that = this

    // @ts-ignore
    if (that.$win && that.$win.vue_wp_plugin_config_admin) {
      that.doLoad()
      return
    }

    document.onreadystatechange = async () => {
      if (document.readyState == "complete") {
        this.doLoad()
      }
    }
  }
})
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
</style>
