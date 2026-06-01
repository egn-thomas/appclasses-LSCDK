const express = require("express");
const router = express.Router();
const { requireRole } = require("../middleware/authMiddleware");
const {
  getAllClasses,
  getClassById,
  createClass,
  updateClass,
  deleteClass,
} = require("../controllers/classController");

// Protéger toutes les routes classes avec les rôles Administrateur et Éditeur
router.use(requireRole(["Administrateur", "Editeur"]));

// Endpoints
router.get("/", getAllClasses);
router.get("/:id", getClassById);
router.post("/", createClass);
router.put("/:id", updateClass);
router.delete("/:id", deleteClass);

module.exports = router;
