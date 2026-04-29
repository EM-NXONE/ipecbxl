/**
 * /etudiant/profil — profil + changement de mot de passe.
 */
import { createFileRoute } from "@tanstack/react-router";
import { useState } from "react";
import { useEtudiantAuth } from "@/lib/auth-etudiant";
import { etuApi } from "@/lib/api";

export const Route = createFileRoute("/etudiant/_authenticated/profil")({
  component: EtudiantProfilPage,
});

function EtudiantProfilPage() {
  const { user } = useEtudiantAuth();
  const [password, setPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [status, setStatus] = useState<{ type: "success" | "error"; msg: string } | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await etuApi.post("/profil.php", { password, new_password: newPassword });
      setStatus({ type: "success", msg: "Mot de passe mis à jour avec succès." });
      setPassword("");
      setNewPassword("");
    } catch (e: any) {
      setStatus({ type: "error", msg: e.message || "Erreur lors de la mise à jour." });
    }
  };

  return (
    <div className="max-w-2xl">
      <h1 className="font-display text-3xl text-cream mb-8">Mon profil</h1>

      <div className="bg-card border border-border/40 rounded-md p-6 mb-8">
        <h2 className="font-display text-lg text-cream mb-4">Informations personnelles</h2>
        <div className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <div className="text-muted-foreground">Nom</div>
            <div className="text-cream">{user?.nom}</div>
          </div>
          <div>
            <div className="text-muted-foreground">Prénom</div>
            <div className="text-cream">{user?.prenom}</div>
          </div>
          <div>
            <div className="text-muted-foreground">Email</div>
            <div className="text-cream">{user?.email}</div>
          </div>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="bg-card border border-border/40 rounded-md p-6">
        <h2 className="font-display text-lg text-cream mb-4">Sécurité</h2>
        {status && (
          <div className={`mb-4 px-4 py-3 rounded-sm text-sm ${status.type === "success" ? "bg-green-500/10 text-green-400 border border-green-500/30" : "bg-destructive/10 text-destructive border border-destructive/30"}`}>
            {status.msg}
          </div>
        )}
        <div className="space-y-4">
          <div>
            <label className="block text-xs uppercase tracking-wider text-muted-foreground mb-1">Mot de passe actuel</label>
            <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} className="w-full bg-background border border-border/40 rounded-sm px-3 py-2 text-cream focus:outline-none focus:border-blue" required />
          </div>
          <div>
            <label className="block text-xs uppercase tracking-wider text-muted-foreground mb-1">Nouveau mot de passe</label>
            <input type="password" value={newPassword} onChange={(e) => setNewPassword(e.target.value)} className="w-full bg-background border border-border/40 rounded-sm px-3 py-2 text-cream focus:outline-none focus:border-blue" required />
          </div>
          <button type="submit" className="bg-blue text-white px-4 py-2 rounded-sm text-sm font-medium hover:bg-blue/90">
            Mettre à jour
          </button>
        </div>
      </form>
    </div>
  );
}
