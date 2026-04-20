<script setup lang="ts">
import type { UserType } from './Utils';

import { useAlert } from '#imports';

interface UserFormI {
	item?: UserType
}
//props
const props = defineProps<UserFormI>();

//model
const valid = ref<boolean>(true);
const showPassword = ref<boolean>(false);
const form = ref<any>({
	name: "",
	email: "",
	password: "",
	id: 0,
	user_type: 'admin',
	site_id: "",
});
const rules = ref<any>({
	required: (value: any) => !!value || "Required.",
	min: (v: any) => String(v).length >= 6 || "Min 8 characters",
});
const { axiosErrorAlert, alertSuccess } = useAlert();
const userTypes = [
	{ id: 'admin', label: 'Admin' },
	{ id: 'operator', label: 'Operator' }
]

//created
if (props.item) {
	form.value = { ...form.value, ...props.item };
}

//watcher
watch(props, () => {
	form.value = { ...form.value, ...props.item };
})

//emitters
const emitters = defineEmits(['users:close-modal', 'users:reload-list']);

//methods
const generatePassword = () => {
	var chars =
		"0123456789abcdefghijklmnopqrstuvwxyz!@#$%^&*()ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	var passwordLength = 6;
	var password = "";

	for (var i = 0; i <= passwordLength; i++) {
		var randomNumber = Math.floor(Math.random() * chars.length);
		password += chars.substring(randomNumber, randomNumber + 1);
	}

	form.value.password = password;
	showPassword.value = true;
}

const validate = async () => {
	const action = typeof props.item?.id !== "undefined" ? "update" : "save";

	const { success, data, error } = await useApiPostFetch(`/users/${action}`, form.value);

	if (success) {
		alertSuccess(data.message);
		emitters('users:close-modal');
		emitters('users:reload-list')
		return;
	}
	console.log(error.response._data);
	if (!success && error) {
		axiosErrorAlert(error);
	}

}
</script>
<template>
	<v-card title="Save User">
		<div class="pa-3">
			<v-text-field v-model="form.name" :rules="[rules.required]" label="Name" variant="solo" density="compact"
				class="filter-search text-field-component" new-password></v-text-field>

			<v-text-field v-model="form.email" :rules="[rules.required]" label="Email" autocomplete="new-password"
				variant="solo" density="compact" class="filter-search text-field-component"></v-text-field>

			<v-select v-model="form.user_type" :items="userTypes" item-title="label" item-value="id" label="User Type" />

			<div class="pb-2" v-if="form.user_type === 'operator'">
				<SelectorOperators v-model="form.site_id" :default-value="form.site_id" />
			</div>

			<div v-if="!item">
				<v-btn elevation="2" small @click="generatePassword" class="mt-2 mb-2">
					Random password
				</v-btn>
				<v-text-field v-model="form.password" :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
					:rules="[rules.required, rules.min]" :type="showPassword ? 'text' : 'password'" name="input-10-1"
					label="Password" hint="At least 8 characters" variant="solo" density="compact"
					class="filter-search text-field-component" autocomplete="new-password" counter
					@click:append="showPassword = !showPassword"></v-text-field>
			</div>

			<v-btn :disabled="!valid" color="success" class="btn btn-primary" type="button" @click="validate">
				<i class="ph ph-floppy-disk" />
				Save
			</v-btn>
		</div>
	</v-card>
</template>
