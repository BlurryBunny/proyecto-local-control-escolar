<template>
  <div class="row align-items-start">
    <!-- Campo para rellenar el correo -->
    <input type="text" class="form-control mb-2" v-model="myEmail" />

    <div class="col">
      <!-- Se corrobora el estado del archivo (cambiar a numerico )-->
      <template v-if="checkUpload() === 1">
        <i>Estado:</i> <i class="text-success">Completado</i>
      </template>
      <template v-else-if="checkUpload() === 0">
        <i>Estado:</i> <i class="text-warning">Esperando respuesta</i>
      </template>
      <template v-else>
        <i>Estado:</i> <i class="text-danger">No se ha enviado correo</i>
      </template>

      <div class="form-group mt-3">
        <button
          @click="enviarCorreoCartaRecomendacion()"
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
  name: "valida-carta-recomendacion",

  props: {
    email: {
      type: String,
      default: "example@example.com",
    },

    recommendation_letter: {
      type: Object,
      default: null,
    },

    archive_recommendation_letter: {
      type: Object,
      default: null,
    },

    appliant: Object,
    academic_program: Object,
  },

  computed:{
     myEmail: {
      get(){
        return this.email;
      },
      set(newVal){
        this.$emit('update:email', newVal);
        this.email = newVal;
      }
    },
  },

  methods: {
    // -1  : Correo no enviado
    //  0  : En espera de respuesta del externo
    //  1  : Completado

    checkUpload() {
      let res = -1;
      // console.log("hola");
      // console.log(this.recommendation_letter );
      if (this.recommendation_letter != null) {
        console.log("object");
        if (this.recommendation_letter["email_evaluator"]) {
          //Correo ya se envio
          res = 0;

          if (this.archive_recommendation_letter != null) {
            if (this.archive_recommendation_letter["location"]) {
              //La carta ha sido contestadad
              res = 1;
            }
          }
        }
      }
      return res;
    },

    enviarCorreoCartaRecomendacion() {
      // //cadena no es similar a las existentes o  es nueva | INSERTAR
      // if(!res){

      let request;
      
      //Ya existe carta de recomendacion
      if (this.recommendation_letter != null) {
        
        request = {
          email: this.email,
          appliant: this.appliant,
          recommendation_letter: this.recommendation_letter,
          academic_program: this.academic_program,
          letter_created: 1,
        };
      } else {
 
        // No existe carta se necesita crear
        request = {
          email: this.email,
          appliant: this.appliant,
          academic_program: this.academic_program,
          letter_created: 0,
        };
      }
      axios
        .post(
          "/controlescolar/solicitud/sentEmailRecommendationLetter",
          request
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