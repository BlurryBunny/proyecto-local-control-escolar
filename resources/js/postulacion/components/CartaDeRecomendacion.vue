<template>
  <!-- verifica si la carta de recomendacion en pdf corresponde a los datos de la tabla
          Si corresponde entonces se ha realizado 
          Si no entonces esta pendiente
         -->
  <div class="row m-1">
    <div
      class="form-group col-md-5"
      v-for="my_email in emails"
    >
      <input
        type="text"
        class="form-control mb-2"
        v-model="my_email.email"
        
      />

      <template v-if="checkUpload()">
        <i>Estado:</i> <i class="text-success">Completado</i>
      </template>
      <template v-else>
        <i>Estado:</i> <i class="text-danger">Sin completar</i>
      </template>
      <div class="form-group col-5 mt-3">
        <button
          @click="enviarCorreoCartaRecomendacion(my_email.email)"
          class="btn btn-primary"
        >
          Enviar correo
        </button>
        
      </div>
    </div>
  </div>
</template>


<script>
export default {
  name: "carta-recomendacion",

  data() {
    return {
      emails: [{email: "example@example.com"}, {email: "example@example.com"}]
    };
  },

  props: {
    //Cartas de recomendacion (tabla a rellenar)
    //Aqui se cambian los correos

    appliant: {
      type: Object,
    },

    academic_program: {
      type: Object,
    },

    //recibe los emails de la carta de recomendacion como en un arreglo para comparar
    recommendation_letter:{
      type: Object,
    }

    // recommendation_letters: {
    //   type: Array,
    //   default: [
    //     {
    //       email_evaluator: null,
    //     },
    //     {
    //       email_evaluator: null,
    //     },
    //   ],
    // },

    // //archivos de carta de recomendacion (contiene el id de carta y locacion para ver si es ciert que guardo)
    // archives_recommendation_letter: {
    //   type: Array,
    //   default: [
    //     {
    //       rl_id: null,
    //     },
    //     {
    //       rl_id: null,
    //     },
    //   ],
    // },
  },

  methods: {
    // checkUpload(id_rl) {
    //   for (archives in archives_recommendation_letter) {
    //     if (id_rl === archives.rl_id) {
    //       return true;
    //     }
    //   }
    //   return false;
    // },

    checkUpload() {
      return true;
    },

    enviarCorreoCartaRecomendacion(my_email) {

      // let res = false;

      // // El email que inserto el usuario esta repetido o ya se envio carta de recomendacion
      // for ( rl in this.recommendation_letter){
      //   if(rl.email_evaluator === my_email.email){
      //       res = true;  
      //   }
      // }


      console.log(my_email);
      
      // //cadena no es similar a las existentes o  es nueva | INSERTAR
      // if(!res){
      axios.post(
          "/controlescolar/solicitud/sentEmailRecommendationLetter",{
            email:my_email,
            appliant: this.appliant,
            recommendation_letter: this.recommendation_letter,
            academic_program: this.academic_program,
          }
        )
        .then((response) => {
          console.log(response);
        })
        .catch((error) => {
          console.log(error.response.data);
        });
      // }
      
    },
  },
};
</script>