<template>
  <div style="height: 100dvh" class="d-flex align-center auth-wrapper">
    <v-container style="height: inherit" class="d-flex">
      <v-row justify="center" class="align-center" no-gutters>
        <v-col cols="12" xl="8" lg="8">
          <v-card>
            <v-row no-gutters>
              <v-col cols="12" lg="12" class="">
                <router-view />
              </v-col>
            </v-row>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>
