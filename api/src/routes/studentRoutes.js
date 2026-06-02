const express = require("express");
const router = express.Router();
const { requireRole } = require("../middleware/authMiddleware");
const {
  getAllStudents,
  getStudentById,
  createStudent,
  updateStudent,
  deleteStudent,
} = require("../controllers/studentController");

// GET endpoints accessible à tous
router.get("/", getAllStudents);
router.get("/:id", getStudentById);

// Protéger les routes de modification avec les rôles Administrateur et Éditeur
router.post("/", requireRole(["Administrateur", "Editeur"]), createStudent);
router.put("/:id", requireRole(["Administrateur", "Editeur"]), updateStudent);
router.delete(
  "/:id",
  requireRole(["Administrateur", "Editeur"]),
  deleteStudent,
);

module.exports = router;
