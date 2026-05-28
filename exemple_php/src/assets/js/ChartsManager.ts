import Chart from "chart.js/auto";

class ChartsManager {
  // On définit les couleurs pour chaque cas (insensible à la casse)
  private static colorMapping: { [key: string]: string } = {
    victoire: "#4ade80", // Vert
    défaite: "#f87171", // Rouge
    nulle: "#9ca3af", // Gris
  };

  public static init(): void {
    const canvases =
      document.querySelectorAll<HTMLCanvasElement>(".chart-canvas");

    canvases.forEach((canvas) => {
      const labels: string[] = JSON.parse(canvas.dataset.chartLabels || "[]");
      const data: number[] = JSON.parse(canvas.dataset.chartData || "[]");
      const labelTitle: string = canvas.dataset.chartLabel || "Statistiques";

      // On génère le tableau de couleurs en fonction des labels
      const backgroundColors = labels.map((label) => {
        const lowerLabel = label.toLowerCase();
        return this.colorMapping[lowerLabel] || "#3b82f6"; // Bleu par défaut si non trouvé
      });

      const chartType = (canvas.dataset.chartType || "pie") as any;

      new Chart(canvas, {
        type: chartType,
        data: {
          labels: labels,
          datasets: [
            {
              label: labelTitle,
              data: data,
              backgroundColor: backgroundColors,
              borderWidth: 1,
            },
          ],
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: "bottom",
              labels: {
                // ICI : On change la couleur de la police des labels
                color: "#ffffff",
                font: {
                  size: 14,
                  weight: "bold",
                },
                padding: 20,
              },
            },
            tooltip: {
              // Optionnel : changer aussi la couleur dans les info-bulles
              titleColor: "#ffffff",
              bodyColor: "#ffffff",
            },
          },
        },
      });
    });
  }
}

document.addEventListener("DOMContentLoaded", () => ChartsManager.init());
