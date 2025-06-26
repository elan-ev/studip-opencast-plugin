<template>
    <div class="oc--admin--section">
        <fieldset class="collapsable" v-if="!disabled">
            <legend>
                {{ $gettext('Server-Wartung') }}
            </legend>

            <span>
                <div>
                    <span>
                        {{ $gettext('Wartungsmodus') }}
                    </span>
                </div>

                <section class="hgroup size-m">
                    <label>
                        <input type="radio" value="off"
                            name="maintenance_mode"
                            :checked="currentConfig.maintenance_mode == 'off'"
                            v-model="currentConfig.maintenance_mode"
                            :disabled="disabled"
                        >
                        {{ $gettext('Aus') }}
                    </label>
                    <label>
                        <input type="radio" value="on"
                            name="maintenance_mode"
                            :checked="currentConfig.maintenance_mode == 'on'"
                            v-model="currentConfig.maintenance_mode"
                            :disabled="disabled"
                        >
                        {{ $gettext('Ein') }}
                    </label>
                    <label>
                        <input type="radio" value="read-only"
                            name="maintenance_mode"
                            :checked="currentConfig.maintenance_mode == 'read-only'"
                            v-model="currentConfig.maintenance_mode"
                            :disabled="disabled"
                        >
                        {{ $gettext('Nur Lesen') }}
                    </label>
                </section>
            </span>

            <label >
                <span>
                    {{ $gettext('Engage-Node ersatz URL') }}
                </span>
                <input type="text"
                    v-model="currentConfig.maintenance_engage_url_fallback"
                    placeholder="https://presentation.opencast.url"
                    :disabled="disabled"
                >
            </label>

            <label v-if="!disabled">
                <span>
                    {{ $gettext('Wartungstext') }}
                </span>

                <I18NText :text="currentConfig.maintenance_text ?? ''"
                    :languages="config_list.languages"
                    @updateValue="setTextValue"
                />
            </label>

        </fieldset>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import I18NText from "@/components/Config/I18NText";

export default {
    name: "MaintenanceOptions",

    components: {
        I18NText
    },

    props: {
        currentConfig: {
            type: Object,
            required: true,
        },
        disabled: {
            type: Boolean,
            default: false
        }
    },

    computed: {
        ...mapGetters(['config_list']),
    },

    methods: {
        setTextValue(newValue) {
            this.currentConfig.maintenance_text = newValue;
        }
    },
}
</script>
