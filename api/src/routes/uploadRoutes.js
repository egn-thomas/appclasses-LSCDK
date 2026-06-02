const express = require("express");
const multer = require("multer");
const uploadController = require("../controllers/uploadController");
const { requireAuth, requireRole } = require("../middleware/authMiddleware");

const router = express.Router();

// Configuration multer
const storage = multer.memoryStorage();
const upload = multer({
  storage,
  limits: { fileSize: 20 * 1024 * 1024 }, // 20MB max
  fileFilter: (req, file, cb) => {
    const allowedMimes = [
      "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", // .xlsx
      "application/vnd.ms-excel", // .xls
      "text/csv",
      "application/csv",
    ];

    const allowedExts = [".xlsx", ".xls", ".csv"];
    const ext = "." + file.originalname.split(".").pop().toLowerCase();

    if (allowedMimes.includes(file.mimetype) || allowedExts.includes(ext)) {
      cb(null, true);
    } else {
      cb(new Error("Format de fichier non supporté"));
    }
  },
});

// Route pour parser un fichier
router.post(
  "/upload",
  requireAuth,
  requireRole(["Administrateur", "Editeur"]),
  upload.single("file"),
  uploadController.parseFile,
);

module.exports = router;
