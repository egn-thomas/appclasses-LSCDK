const express = require("express");
const router = express.Router();
const { requireRole } = require("../middleware/authMiddleware");
const {
  getAllOptions,
  getOptionById,
  createOption,
  updateOption,
  deleteOption,
} = require("../controllers/optionController");

// Protéger toutes les routes options avec les rôles Administrateur et Éditeur
router.use(requireRole(["Administrateur", "Editeur"]));

// Endpoints
router.get("/", getAllOptions);
router.get("/:id", getOptionById);
router.post("/", createOption);
router.put("/:id", updateOption);
router.delete("/:id", deleteOption);

module.exports = router;
