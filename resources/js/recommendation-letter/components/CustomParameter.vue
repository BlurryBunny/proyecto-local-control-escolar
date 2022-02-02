<template>
    <div>
        <input type="text" class="form-control" v-model="Name">
    <span>
        <i class="fas fa-minus-circle" @click="remove(index)" ></i>
    </span>
    </div>

</template>


<script>
export default {
    name: 'analysis-parameter',

    props: {
        index:{
            type: Number,
            default: null,
        },
        // Nombre del parámetro
        name: {
            type: String,
            default: null,
        },
    
        // Valor del parámetro
        score: {
            type: String,
            default: null,
        },

        //valuesRadioBtns: ['Excelente','Bueno','Regular','Deficiente']
    },

    computed: {
        // Devuelve o altera el nivel del parámetro.
        Score: {
            get() {
                return this.score;
            },

            set(value) {
                this.$emit('update:score', value);
            }
        },
        Name: {
            get(){
                return this.name
            },
            set(value){
                this.$emit('update:name',value)
            }
        }
    },

    methods: {
        add(index) {
            this.inputs.push({ name: '' });
        },
        remove(index) {
            this.inputs.splice(index, 1);
        }
    }
}
</script>