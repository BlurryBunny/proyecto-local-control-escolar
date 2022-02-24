<template>
  <!-- verifica si la carta de recomendacion en pdf corresponde a los datos de la tabla
          Si corresponde entonces se ha realizado 
          Si no entonces esta pendiente
         -->
  <div>
    <!-- <div class="row">
      <div
        class="form-group col-md-5"
        v-for="(rl, index) in recommendation_letters"
        :key="index"
      >
        <label> Correo para enviar carta de intencion #{{ index + 1 }} </label>

        <input
          type="text"
          class="form-control"
          v-model="rl[index].email_evaluator"
        />

        
        <template v-if="checkUpload(recommendation_letters[index].id)">
          <i>Estado:</i> <i class="text-success">Completado</i>
        </template>
        <template v-else>
          <i>Estado:</i> <i class="text-danger">Sin completar</i>
        </template>
      </div>
    </div>

    <div class="row justify-content-end">
      <button
        @click="actualizaCorreosCartaRecomendacion"
        class="mx-2 btn btn-primary"
      >
        Guardar
      </button>

      <button
        @click="enviarCorreoCartaRecomendacion"
        class="mx-2 btn btn-primary"
      >
        Guardar
      </button>
    </div> -->

    <div class="row">
      <div class="form-group col-5">
        <input type="text" class="form-control mb-2" v-model="email1" />

        <template v-if="checkUpload()">
          <i>Estado:</i> <i class="text-success">Completado</i>
        </template>
        <template v-else>
          <i>Estado:</i> <i class="text-danger">Sin completar</i>
        </template>

        <div class="form-group col-5 mt-3">
          <button type="submit" class="btn btn-primary">Enviar correo</button>
        </div>
      </div>

      <div class="form-group col-5">
        <input type="text" class="form-control mb-2" v-model="email2" />

        <template v-if="checkUpload()">
          <i>Estado:</i> <i class="text-success">Completado</i>
        </template>
        <template v-else>
          <i>Estado:</i> <i class="text-danger">Sin completar</i>
        </template>

        <div class="form-group col-5 mt-3">
        <button type="submit" class="btn btn-primary">Enviar correo</button>
      </div>
      </div>

      
    </div>

    <!-- <div class="row mt-2">
      <div class="form-group col-5">
        <button type="submit" class="btn btn-primary">
                Guardar
            </button>
      </div>
    </div> -->
  </div>

  <!--   
  <div class="row">
      <div 
      class="form-group col-5"
      v-for="(rl, index) in recommendation_letters"
      :key="index"
        >
          <input type="text" class="form-control" v-model="rl[index].email_evaluator" />
        <template v-if="checkUpload(rl)">
          <i>Estado:</i> <i class="text-success">Completado</i>
        </template>
        <template v-else>
          <i>Estado:</i> <i class="text-danger">Sin completar</i>
        </template>
      </div>
  </div> -->
</template>


<script>
export default {
  name: "carta-recomendacion",

  props: {
    //Cartas de recomendacion (tabla a rellenar)
    //Aqui se cambian los correos

    appliant:{
      type:Object
    },

    recommendation_letters: {
      type: Array,
      default: [
        {
          email_evaluator: null,
        },
        {
          email_evaluator: null,
        },
      ],
    },

    //archivos de carta de recomendacion (contiene el id de carta y locacion para ver si es ciert que guardo)
    archives_recommendation_letter: {
      type: Array,
      default: [
        {
          rl_id: null,
        },
        {
          rl_id: null,
        },
      ],
    },

    // emails: {
    //   type: Array,
    //   default: ["example@example.com", "example@example.com"],
    // },

    email1: {
      type: String,
      default: "",
    },

    email2: {
      type: String,
      default: "",
    },
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

    // actualizaCorreosCartaRecomendacion(evento) {
    //   this.errores = {};

    //   axios
    //     .post("/controlescolar/postulacion/updateMailRecommendationLetter", {
    //       id: this.id,
    //       archive_id: this.archive_id,
    //       state: estado,
    //       course_name: this.course_name,
    //       assisted_at: this.assisted_at,
    //       scolarship_level: this.scolarship_level,
    //     })
    //     .then((response) => {
    //       Object.keys(response.data).forEach((dataKey) => {
    //         var event = "update:" + dataKey;
    //         this.$emit(event, response.data[dataKey]);
    //       });
    //     })
    //     .catch((error) => {
    //       this.State = "Incompleto";
    //       var errores = error.response.data["errors"];

    //       Object.keys(errores).forEach((key) => {
    //         Vue.set(this.errores, key, errores[key][0]);
    //       });
    //     });
    // },

    enviarCorreoCartaRecomendacion(email) {
      //validacion de datos

      axios.post("/controlescolar/postulacion/sentEmailRecommendationLetter",email,this.appliant);
      //enviar correo

      this.enviaExperienciaLaboral(evento, "Completo");
    },
  },
};
</script>