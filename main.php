<?php
require 'vendor/autoload.php';

final class Collection
{
	private $items = [];

	public function add($item)
	{
		$this->items[] = $item;
	}

	public function getItems()
	{
		return $this->items;
	}
}

final class Equipement
{
	private $libelle;
	private $description;

	public function __construct($libelle, $description)
	{
		$this->libelle = $libelle;
		$this->description = $description;
	}

	public function getLibelle()
	{
		return $this->libelle;
	}

	public function getDescription()
	{
		return $this->description;
	}
}

final class BateauVoyageur
{
	private $id;
	private $nom;
	private $type;
	private $capacite;
	private $vitesse;
	private $description;
	private $equipements;

	public function __construct($id, $nom, $type, $capacite, $vitesse, $description)
	{
		$this->id = $id;
		$this->nom = $nom;
		$this->type = $type;
		$this->capacite = $capacite;
		$this->vitesse = $vitesse;
		$this->description = $description;
		$this->equipements = new Collection();
	}

	public function ajouterEquipement(Equipement $equipement)
	{
		$this->equipements->add($equipement);
	}

	public function getNom()
	{
		return $this->nom;
	}

	public function getType()
	{
		return $this->type;
	}

	public function getCapacite()
	{
		return $this->capacite;
	}

	public function getVitesse()
	{
		return $this->vitesse;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function getEquipements()
	{
		return $this->equipements->getItems();
	}
}

/** Acces aux lignes issues de dbBat. Remplacable par une vraie source SQL. */
final class JeuEnregistrement
{
	/** @return array<int, array<string, mixed>> */
	public function bateauxVoyageurs(): array
	{
		return [
			[
				'id' => 1,
				'nom' => 'Armorique',
				'type' => 'Ferry',
				'capacite' => 1_500,
				'vitesse' => 21.5,
				'description' => 'Un ferry confortable adapte aux liaisons regulieres du littoral breton.',
				'equipements' => [
					['libelle' => 'Salon panoramique', 'description' => 'Espace couvert avec vue sur la mer.'],
					['libelle' => 'Restauration', 'description' => 'Bar et restauration rapide a bord.'],
					['libelle' => 'Acces PMR', 'description' => 'Acces facilite et emplacements reserves.'],
				],
			],
			[
				'id' => 2,
				'nom' => 'Enez Eussa',
				'type' => 'Vedette a passagers',
				'capacite' => 250,
				'vitesse' => 28.0,
				'description' => 'Une vedette rapide pour rejoindre les iles en toute simplicite.',
				'equipements' => [
					['libelle' => 'Sieges interieurs', 'description' => 'Sieges individuels avec rangements.'],
					['libelle' => 'Pont exterieur', 'description' => 'Pont ouvert pour profiter du voyage.'],
					['libelle' => 'Gilets de sauvetage', 'description' => 'Equipement de securite disponible pour chaque voyageur.'],
				],
			],
			[
				'id' => 3,
				'nom' => 'Ile de Groix',
				'type' => 'Catamaran',
				'capacite' => 850,
				'vitesse' => 24.0,
				'description' => 'Un catamaran stable et lumineux pour les traverses vers les iles.',
				'equipements' => [
					['libelle' => 'Cabine familiale', 'description' => 'Espace convivial pour les familles.'],
					['libelle' => 'Table a langer', 'description' => 'Equipement disponible dans les sanitaires.'],
					['libelle' => 'Wi-Fi', 'description' => 'Connexion disponible dans les espaces interieurs.'],
				],
			],
		];
	}
}

final class Passerelle
{
	private $jeu;

	public function __construct(JeuEnregistrement $jeu)
	{
		$this->jeu = $jeu;
	}

	public function getBateauxVoyageurs()
	{
		$bateaux = new Collection();

		foreach ($this->jeu->bateauxVoyageurs() as $ligne) {
			$bateau = new BateauVoyageur(
				$ligne['id'],
				$ligne['nom'],
				$ligne['type'],
				$ligne['capacite'],
				$ligne['vitesse'],
				$ligne['description']
			);

			foreach ($ligne['equipements'] as $equipement) {
				$bateau->ajouterEquipement(new Equipement($equipement['libelle'], $equipement['description']));
			}

			$bateaux->add($bateau);
		}

		return $bateaux;
	}
}

final class PDF extends FPDF
{
}

final class BrochurePDF
{
	private $passerelle;

	public function __construct(Passerelle $passerelle)
	{
		$this->passerelle = $passerelle;
	}

	public function editer()
	{
		$pdf = new PDF();
		$bateaux = $this->passerelle->getBateauxVoyageurs();
		$pdf->AddPage();
		$pdf->SetFont('Arial', 'B', 18);
		$pdf->Cell(0, 15, 'PITAINE - Bateaux voyageurs');
		$pdf->Ln(20);
		$pdf->SetFont('Arial', '', 10);
		foreach ($bateaux as $bateau) {
			$pdf->SetFont('Arial', 'B', 13);
			$pdf->Cell(0, 8, $bateau->getNom());
			$pdf->Ln();
			$pdf->SetFont('Arial', '', 10);
			$pdf->MultiCell(0, 6, $bateau->getType() . ' - ' . $bateau->getCapacite() . ' passagers - ' . $bateau->getVitesse() . ' noeuds');
			$pdf->MultiCell(0, 6, $bateau->getDescription());
			$pdf->Cell(0, 6, 'Equipements :');
			$pdf->Ln();
			foreach ($bateau->getEquipements() as $equipement) {
				$pdf->Cell(10);
				$pdf->Cell(0, 6, '- ' . $equipement->getLibelle());
				$pdf->Ln();
			}
			$pdf->Ln(8);
		}

		return $pdf->Output('S');
	}
}

$contenuPdf = (new BrochurePDF(new Passerelle(new JeuEnregistrement())))->editer();

if (PHP_SAPI === 'cli') {
	$chemin = __DIR__ . DIRECTORY_SEPARATOR . 'BateauVoyageur.pdf';
	file_put_contents($chemin, $contenuPdf);
	fwrite(STDOUT, "Brochure generee : {$chemin}\n");
	exit(0);
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="BateauVoyageur.pdf"');
header('Content-Length: ' . strlen($contenuPdf));
echo $contenuPdf;
