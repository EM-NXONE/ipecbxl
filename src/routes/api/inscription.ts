import { createFileRoute } from "@tanstack/react-router";
import { z } from "zod";

const InscriptionSchema = z.object({
  civilite: z.string().trim().min(1).max(30),
  prenom: z.string().trim().min(1).max(100),
  nom: z.string().trim().min(1).max(100),
  dateNaissance: z.string().trim().min(1).max(20),
  nationalite: z.string().trim().min(1).max(100),
  email: z.string().trim().email().max(255),
  telephone: z.string().trim().max(30).optional().default(""),
  adresse: z.string().trim().min(1).max(250),
  paysResidence: z.string().trim().min(1).max(100),
  programme: z.enum(["PAA", "PEA"]),
  annee: z.string().trim().min(1).max(80),
  specialisation: z.string().trim().min(1).max(80),
  rentree: z.string().trim().min(1).max(120),
  message: z.string().trim().max(1500).optional().default(""),
});

export const Route = createFileRoute("/api/inscription")({
  server: {
    handlers: {
      POST: async ({ request }) => {
        const mailerUrl = process.env.INSCRIPTION_MAILER_URL;
        const mailerToken = process.env.INSCRIPTION_MAILER_TOKEN;

        if (!mailerUrl || !mailerToken) {
          console.error("Missing INSCRIPTION_MAILER_URL or INSCRIPTION_MAILER_TOKEN");
          return Response.json(
            { error: "Service indisponible. Réessayez plus tard." },
            { status: 503 },
          );
        }

        let raw: unknown;
        try {
          raw = await request.json();
        } catch {
          return Response.json({ error: "Requête invalide" }, { status: 400 });
        }

        const parsed = InscriptionSchema.safeParse(raw);
        if (!parsed.success) {
          return Response.json(
            { error: "Champs invalides", details: parsed.error.flatten() },
            { status: 400 },
          );
        }

        try {
          const response = await fetch(mailerUrl, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-Auth-Token": mailerToken,
            },
            body: JSON.stringify(parsed.data),
          });

          if (!response.ok) {
            const text = await response.text();
            console.error("Mailer PHP error", response.status, text);
            return Response.json(
              { error: "Échec de l'envoi de la candidature." },
              { status: 502 },
            );
          }

          return Response.json({ ok: true });
        } catch (err) {
          console.error("Mailer fetch failed", err);
          return Response.json(
            { error: "Impossible de joindre le service d'envoi." },
            { status: 502 },
          );
        }
      },
    },
  },
});
