import js from "@eslint/js";
import globals from "globals";

export default [
  {
    ignores: [
      "**/vendor/**",
      "**/node_modules/**",
      "**/.git/**",
      ".phpunit.cache/**",
      "v-update-api/public/assets/lib/**",
    ],
  },
  js.configs.recommended,
  {
    files: ["**/*.js"],
    languageOptions: {
      ecmaVersion: 2021,
      sourceType: "module",
      globals: { ...globals.browser, Dropzone: "readonly" },
    },
    rules: {
      "no-unused-vars": "warn",
      "no-console": "off",
    },
  },
  {
    files: ["**/*.mjs"],
    languageOptions: {
      ecmaVersion: 2021,
      sourceType: "module",
      globals: globals.node,
    },
  },
];
