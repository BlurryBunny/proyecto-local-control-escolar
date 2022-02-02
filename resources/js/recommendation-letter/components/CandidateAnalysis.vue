<template>
<div >
    <table class="col-lg-12 candidate-analysis-table">
        <thead>
            <tr  class="align-items-start">
                <th> Parámetros </th>
                <th> Excelente </th>
                <th> Bueno </th>
                <th> Regular </th>
                <th> Deficiente </th>
            </tr>
        </thead>
        <tbody>
            <analysis-parameter v-for="parameter in parameters" 
                :key="parameter.name"
                v-bind="parameter"
                :score.sync="parameter.score"
            >
            </analysis-parameter>
            
        </tbody>
         <!-- <div v-for ="parameter in parameters" :key ="parameter.name" class="mt-3">Selected: <strong>{{ parameter.score }}</strong></div> -->
        <tfoot  class="d-block">
            <analysis-parameter  v-for="(parameter,index) in newParameter" 
                :key="index"
                v-bind="parameter"
                :score.sync="parameter.score"
                :name.sync="parameter.name"
                >
            </analysis-parameter>

            <tr>
                <td colspan="5">
                    <label class="d-block mt-3">Otra que desee especificar</label>
                    <button class="btn btn-primary">Agregar otro parámtero</button>
                </td>
            </tr>
        </tfoot>
        
    </table>
    </div>
</template>

<script>
import AnalysisParameter from './AnalysisParameter.vue';

export default {
    name: 'candidate-analysis',

    components: {
        AnalysisParameter
    },

    props: {
        
        // Otros parámetros que se quieran especificar, para la carta 
        // de recomendación.
        other_skills: {
            type: Array,
            default() {
                return [];
            }
        },

        newParameter:{
            type: Array,
            default() {
                return[];
                }
        },

        // Parámetros para el análisis del candidato.
        parameters: {
            type: Array,
            default() {
                return [{
                    name: 'Conocimiento y destrezas en su campo',
                    score: null,
                },{
                    name: 'Dedicación al trabajo',
                    score: null, 
                },{
                    name: 'Imaginación y creatividad',
                    score: null, 
                },{
                    name: 'Habilidad para comunicarse',
                    score: null, 
                },{
                    name: 'Rendimiento',
                    score: null, 
                },{
                    name: 'Perseverancia',
                    score: null, 
                },{
                    name: 'Capacidad de convivencia con otras personas',
                    score: null, 
                },{
                    name: 'Disciplina de estudio',
                    score: null, 
                },{
                    name: 'Habilidad de investigación',
                    score: null, 
                },{
                    name: 'Habilidades de comunicación oral y escrita',
                    score: null, 
                },{
                    name: 'Hábitos de trabajo',
                    score: null, 
                },{
                    name: 'Organización',
                    score: null, 
                },{
                    name: 'Planificación',
                    score: null, 
                },{
                    name: 'Oportunidad',
                    score: null, 
                },{
                    name: 'Colaboración en equipo',
                    score: null, 
                },{
                    name: 'Iniciativa propia',
                    score: null, 
                }];
            }
        }
    }
}
</script>