<?php
declare(strict_types=1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include "leader-functions.php";
include "enums/enum-country.php";
include "classes/class-player.php";
$password_errors = [];
$email_errors = [];
$first_name_errors = [];
$last_name_errors = [];
$nickname_errors = [];
$city_errors = [];
$country_errors = [];
$file_errors = [];
$leader_username = $_SESSION['username'] ?? "";
$is_player_manage_valid = false;
$player = null;
$temp_pl = null;
$player_profile_image = null;
$player_id_to_update = null;
$updated_nickname = null;
$player_professional_to_update = null;
$player_country_to_update = null;

if (isset ($_GET['login'])) {
    if ($_GET['login'] !== 'true') {
        $is_login_valid = false;
    } else if ($_GET['login'] === 'true') {
        $is_login_valid = true;
    }
} else {
    $is_login_valid = false;
}

$file = "players.txt";
if (!file_exists($file)) {
    $playersfile = fopen($file, 'w');
    fclose($playersfile);
}
$playersfile = fopen($file, 'r');
$players = [];
while (($line = fgets($playersfile)) !== false) {
    if (!empty (trim($line))) {
        $playerData = explode('~', $line);
        $temp_pl = new Player(
            intval($playerData[0]),
            $playerData[1],
            $playerData[2],
            $playerData[3],
            $playerData[4],
            Country::from($playerData[5]),
            trim($playerData[6]) == 'yes' ? true : false
        );
        array_push($players, $temp_pl);
    }
}
fclose($playersfile);
usort($players, function ($a, $b) {
    return strcasecmp($a->get_last_name(), $b->get_last_name());
});

if (isset ($_GET['action']) && $_GET['action'] === "update" && isset ($_GET['nickname'])) {
    $nickname_to_find = htmlspecialchars(trim($_GET['nickname']));

    foreach ($players as $key => $pl) {
        if ($pl->get_nickname() === $nickname_to_find) {
            $player = $pl;
            $player_id_to_update = $pl->get_id();
            $player_country_to_update = Country::from($pl->get_country());
            $player_professional_to_update = $pl->get_professional();
            $updated_nickname = $pl->get_nickname();
            $image_file_path = "images/players-profile-images/{$updated_nickname}-profile-image.png";
            if (file_exists($image_file_path)) {
                $player_profile_image = file_get_contents($image_file_path);
            }
            unset($players[$key]);
            break;
        }
    }
}

if (isset ($_POST['submit-player']) && ($_GET['action'] == 'add' || $_GET['action'] == 'update')) {

    $first_name_errors = validate_name(isset ($_POST['first-name']) ? htmlspecialchars($_POST['first-name']) : "");
    $last_name_errors = validate_name(isset ($_POST['last-name']) ? htmlspecialchars($_POST['last-name']) : "");
    $nickname_errors = validate_nickname(isset ($_POST['nickname']) ? htmlspecialchars($_POST['nickname']) : "", $players);
    $city_errors = validate_city(isset ($_POST['city']) ? htmlspecialchars($_POST['city']) : "");
    $country_errors = validate_country(isset ($_POST['country']) ? htmlspecialchars($_POST['country']) : "");
    $image = isset ($_FILES['image']) ? $_FILES['image'] : [];
    $file_errors = validate_file($image);
    $delete_current_image = isset ($_POST['delete-current-image']);
    $player_professional_to_update = isset ($_POST['professional']) ? true : false;
    $player_country_to_update = isset ($_POST['country']) && empty ($country_errors) ? Country::from(htmlspecialchars($_POST['country'])) : Country::from("Select a Country");

    if (empty ($first_name_errors) && empty ($last_name_errors) && empty ($nickname_errors) && empty ($city_errors) && empty ($country_errors) && empty ($file_errors)) {
        $unique_id = 1;
        if (!empty ($players)) {
            $max_id = 0;

            foreach ($players as $player) {
                $player_id = $player->get_id();
                if ($player_id > $max_id) {
                    $max_id = $player_id;
                }
            }

            $unique_id = $max_id + 1;
        }


        if ($_GET['action'] == 'add') {
            move_uploaded_file($_FILES['image']['tmp_name'], "images/players-profile-images/" . htmlspecialchars($_POST['nickname']) . "-profile-image.png");
        }

        if ($_GET['action'] == "update") {
            if ($updated_nickname !== htmlspecialchars($_POST['nickname'])) {
                $old_image_file_path = "images/players-profile-images/" . $updated_nickname . "-profile-image.png";
                if (file_exists($old_image_file_path)) {
                    $new_image_file_path = "images/players-profile-images/" . htmlspecialchars($_POST['nickname']) . "-profile-image.png";
                    rename($old_image_file_path, $new_image_file_path);
                }
            }

            if ($image['error'] == UPLOAD_ERR_OK) {
                $file_errors = validate_file($image);

                if (empty ($file_errors)) {
                    $image_file_path = "images/players-profile-images/" . htmlspecialchars($_POST['nickname']) . "-profile-image.png";
                    move_uploaded_file($image['tmp_name'], $image_file_path);
                }
            } elseif ($delete_current_image) {
                $image_file_path = "images/players-profile-images/" . htmlspecialchars($_POST['nickname']) . "-profile-image.png";
                if (file_exists($image_file_path)) {
                    unlink($image_file_path);
                }
            }
        }
        $player = new Player(
            $_GET['action'] == "add" ? $unique_id : $player_id_to_update,
            isset ($_POST['first-name']) && empty ($first_name_errors) ? htmlspecialchars($_POST['first-name']) : $player->get_first_name(),
            isset ($_POST['last-name']) && empty ($last_name_errors) ? htmlspecialchars($_POST['last-name']) : $player->get_last_name(),
            isset ($_POST['nickname']) && empty ($nickname_errors) ? htmlspecialchars($_POST['nickname']) : $player->get_nickname(),
            isset ($_POST['city']) && empty ($city_errors) ? htmlspecialchars($_POST['city']) : $player->get_city(),
            isset ($_POST['country']) && empty ($country_errors) ? Country::from(htmlspecialchars($_POST['country'])) : Country::from($player->get_country()),
            isset ($_POST['professional']) ? true : false
        );

        array_push($players, $player);
        usort($players, function ($a, $b) {
            return $a->get_id() <=> $b->get_id();
        });

        $file_name = "players.txt";
        $file = fopen($file_name, 'w');
        foreach ($players as $current_player) {
            $new_record = $current_player->get_id() . "~" . $current_player->get_first_name() . "~" . $current_player->get_last_name() . "~" . $current_player->get_nickname() . "~" . $current_player->get_city() . "~" . $current_player->get_country() . "~" . ($current_player->get_professional() == true ? 'yes' : 'no') . "\n";
            fwrite($file, $new_record);
        }
        fclose($file);

        $is_player_manage_valid = true;
    } else {
        $is_player_manage_valid = false;
    }
}

if (isset ($_GET['action']) && $_GET['action'] === "delete" && isset ($_GET['nickname'])) {
    $nickname_to_delete = htmlspecialchars(trim($_GET['nickname']));

    foreach ($players as $key => $p) {
        if ($p->get_nickname() === $nickname_to_delete) {
            $player = $p;
            break;
        }
    }

    $players = array_filter($players, function ($p) use ($nickname_to_delete) {
        return $p->get_nickname() !== $nickname_to_delete;
    });

    usort($players, function ($a, $b) {
        return $a->get_id() - $b->get_id();
    });

    $image_file_path = "images/players-profile-images/{$nickname_to_delete}-profile-image.png";
    if (file_exists($image_file_path)) {
        $player_profile_image = file_get_contents($image_file_path);
        unlink($image_file_path);
    }

    $players_file = fopen("players.txt", 'w');
    foreach ($players as $id => $current_player) {
        $new_record = ++$id . "~" . $current_player->get_first_name() . "~" . $current_player->get_last_name() . "~" . $current_player->get_nickname() . "~" . $current_player->get_city() . "~" . $current_player->get_country() . "~" . ($current_player->get_professional() == true ? 'yes' : 'no') . "\n";
        fwrite($players_file, $new_record);
    }
    fclose($players_file);
}



if (isset ($_POST['submit-login'])) {
    $email_errors = validate_email(isset ($_POST['email']) ? $_POST['email'] : "");
    $password_errors = validate_password(isset ($_POST['password']) ? $_POST['password'] : "");

    if (empty ($email_errors) && empty ($password_errors)) {
        $_SESSION['username'] = explode('@', $_POST['email'])[0];
        $leader_username = $_SESSION['username'];
        $is_login_valid = true;
    } else {
        $is_login_valid = false;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player League</title>
    <link rel="stylesheet" href="./styles/player-league.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <?php if (!$is_login_valid): ?>
        <div class="form-container">
            <div>
                <h2>Team Leader Login</h2>
                <form class="login-form" method="post" action="">
                    <div class="form-group">
                        <div class="label">
                            <label class="<?= !empty ($email_errors) ? "label-error" : "" ?>" for="email">Email
                                <span>*</span></label>
                        </div>
                        <input type="text" id="email" name="email" placeholder="JohnDoe@example.com"
                            class="<?= !empty ($email_errors) ? "input-error" : "input" ?>"
                            value="<?= isset ($_POST['email']) ? $_POST['email'] : "" ?>">
                        <div class=" error-log">
                            <?php if (!empty ($email_errors)): ?>
                                <?php foreach ($email_errors as $error): ?>
                                    <h5>
                                        <i class='bx bx-error-circle'></i>
                                        <?= $error ?>
                                    </h5>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="label">
                            <label for="password" class="<?= !empty ($password_errors) ? "label-error" : "" ?>">Password
                                <span>*</span></label>
                        </div>
                        <input type="password" id="password" name="password"
                            class="<?= !empty ($password_errors) ? "input-error" : "input" ?>"
                            value="<?= isset ($_POST['password']) ? $_POST['password'] : "" ?>">
                        <div class="error-log">
                            <?php if (!empty ($password_errors)): ?>
                                <?php foreach ($password_errors as $error): ?>
                                    <h5>
                                        <i class='bx bx-error-circle'></i>
                                        <?= $error ?>
                                    </h5>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
            </div>
            <div class="form-group">
                <button type="submit" name="submit-login">Login</button>
            </div>
            <p class="sign-up-link">Don't have an account? <a href="">Sign up</a></p>
            </form>
        </div>
    <?php else: ?>
        <?php if (isset ($_GET['action'])): ?>
            <?php if ($_GET['action'] === "update" || $_GET['action'] === "add"): ?>
                <?php if (($_GET['action'] === "update" && isset ($player)) || ($_GET['action'] === "add")): ?>
                    <?php if (!$is_player_manage_valid): ?>
                        <div class="nav">
                            <a class="action-button" href="./player-league.php?login=true"><i class='bx bx-arrow-back'></i>
                                Back</a></button>
                        </div>

                        <div class="form-container">
                            <h2>
                                <?= $_GET['action'] == "add" ? "Add Player" : "Update Player" ?>
                            </h2>

                            <form class="add-player-form" action="" method="post" enctype="multipart/form-data">

                                <div class="form-group">
                                    <div class="label">
                                        <label class="<?= !empty ($first_name_errors) ? "label-error" : "" ?>" for="first-name">First Name
                                            <span>*</span></label>
                                    </div>
                                    <input type="text" id="first-name" name="first-name" placeholder="John"
                                        class="<?= !empty ($first_name_errors) ? "input-error" : "input" ?>"
                                        value="<?= ($_GET['action'] == "add" ? (isset ($_POST['first-name']) ? $_POST['first-name'] : "") : ($_GET['action'] == "update" ? (isset ($_POST['first-name']) ? $_POST['first-name'] : $player->get_first_name()) : "")) ?>">
                                    <div class=" error-log">
                                        <?php if (!empty ($first_name_errors)): ?>
                                            <?php foreach ($first_name_errors as $error): ?>
                                                <h5>
                                                    <i class='bx bx-error-circle'></i>
                                                    First Name
                                                    <?= $error ?>
                                                </h5>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>


                                <div class="form-group">
                                    <div class="label">
                                        <label class="<?= !empty ($last_name_errors) ? "label-error" : "" ?>" for="last-name">Last Name
                                            <span>*</span></label>
                                    </div>
                                    <input type="text" id="last-name" name="last-name" placeholder="Smith"
                                        class="<?= !empty ($last_name_errors) ? "input-error" : "input" ?>"
                                        value="<?= ($_GET['action'] == "add" ? (isset ($_POST['last-name']) ? $_POST['last-name'] : "") : ($_GET['action'] == "update" ? (isset ($_POST['last-name']) ? $_POST['last-name'] : $player->get_last_name()) : "")) ?>">
                                    <div class=" error-log">
                                        <?php if (!empty ($last_name_errors)): ?>
                                            <?php foreach ($last_name_errors as $error): ?>
                                                <h5>
                                                    <i class='bx bx-error-circle'></i>
                                                    Last Name
                                                    <?= $error ?>
                                                </h5>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="label">
                                        <label class="<?= !empty ($nickname_errors) ? "label-error" : "" ?>" for="nickname">Nickname
                                            <span>*</span></label>
                                    </div>
                                    <input type="text" id="nickname" name="nickname" placeholder="Smitty"
                                        class="<?= !empty ($nickname_errors) ? "input-error" : "input" ?>"
                                        value="<?= ($_GET['action'] == "add" ? (isset ($_POST['nickname']) ? $_POST['nickname'] : "") : ($_GET['action'] == "update" ? (isset ($_POST['nickname']) ? $_POST['nickname'] : $player->get_nickname()) : "")) ?>">
                                    <div class=" error-log">
                                        <?php if (!empty ($nickname_errors)): ?>
                                            <?php foreach ($nickname_errors as $error): ?>
                                                <h5>
                                                    <i class='bx bx-error-circle'></i>
                                                    <?= $error ?>
                                                </h5>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="label">
                                        <label class="<?= !empty ($city_errors) ? "label-error" : "" ?>" for="city">City
                                            <span>*</span></label>
                                    </div>
                                    <input type="text" id="city" name="city" placeholder="Ottawa"
                                        class="<?= !empty ($city_errors) ? "input-error" : "input" ?>"
                                        value="<?= ($_GET['action'] == "add" ? (isset ($_POST['city']) ? $_POST['city'] : "") : ($_GET['action'] == "update" ? (isset ($_POST['city']) ? $_POST['city'] : $player->get_city()) : "")) ?>">
                                    <div class=" error-log">
                                        <?php if (!empty ($city_errors)): ?>
                                            <?php foreach ($city_errors as $error): ?>
                                                <h5>
                                                    <i class='bx bx-error-circle'></i>
                                                    <?= $error ?>
                                                </h5>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="label">
                                        <label class="<?= !empty ($country_errors) ? "label-error" : "" ?>" for="country">Country
                                            <span>*</span></label>
                                    </div>
                                    <select name="country" id="country" class="<?= !empty ($country_errors) ? "input-error" : "input" ?>"
                                        value="<?= ($_GET['action'] == "add" ? (isset ($_POST['country']) ? $_POST['country'] : "") : ($_GET['action'] == "update" ? "" : "")) ?>">
                                        <?php foreach (Country::cases() as $country): ?>
                                            <option value="<?= $country->value ?>" <?= ($_GET['action'] == "add" ? (isset ($_POST['country']) && $_POST['country'] == $country->value ? "selected" : "") : ($_GET['action'] == "update" ? ($player_country_to_update->value == $country->value ? "selected" : "") : "")) ?>>
                                                <?= $country->value ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="error-log">
                                        <?php if (!empty ($country_errors)): ?>
                                            <?php foreach ($country_errors as $error): ?>
                                                <h5>
                                                    <i class='bx bx-error-circle'></i>
                                                    <?= $error ?>
                                                </h5>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="label">
                                        <label class="<?= !empty ($file_errors) ? "label-error" : "" ?>" for="image">Profile Photo</label>
                                    </div>
                                    <input type="hidden" name="MAX_FILE_SIZE" value="2000000">
                                    <input type="file" id="image" name="image"
                                        class="<?= !empty ($file_errors) ? "input-error" : "input" ?>">
                                    <div class=" error-log">
                                        <?php if (!empty ($file_errors)): ?>
                                            <?php foreach ($file_errors as $error): ?>
                                                <h5>
                                                    <i class='bx bx-error-circle'></i>
                                                    <?= $error ?>
                                                </h5>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty ($player_profile_image)): ?>
                                    <div class="form-group">
                                        <div>
                                            <label class="label" for="delete-current-image">Delete Current Profile Image?</label>
                                        </div>
                                        <div class="checkbox">
                                            <div>
                                                <input type="checkbox" id="delete-current-image" name="delete-current-image" class="input">
                                                <label for="delete-current-image">Yes</label>
                                            </div>
                                            <img src="data:image/png;base64,<?= base64_encode($player_profile_image) ?>"
                                                alt="current-profile-image">
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="form-group">
                                    <div class="">
                                        <label class="label" for="professional">Is Professional?</label>
                                    </div>
                                    <div class="checkbox">
                                        <?php
                                        $professional_checked = '';
                                        if ($_GET['action'] === 'update') {
                                            if ($player_professional_to_update == true & !isset ($_POST['professional']))
                                                $professional_checked = "checked";
                                            elseif (isset ($_POST['professional'])) {
                                                $professional_checked = "checked";
                                            } else {
                                                $professional_checked = "";
                                            }
                                        } elseif ($_GET['action'] === 'add') {
                                            if (!isset ($_POST['professional']))
                                                $professional_checked = "";
                                            else
                                                $professional_checked = "checked";
                                        }
                                        ?>
                                        <input type="checkbox" <?= $professional_checked ?> id="professional" name="professional"
                                            class="input">
                                        <label for="professional">Yes</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" name="submit-player">
                                        <?= $_GET['action'] == "add" ? "Add Player" : "Update Player" ?>
                                    </button>
                                </div>

                            </form>
                        </div>
                    <?php else: ?>

                        <h1><i class='bx bx-check-circle'></i> Player Sucessfully
                            <?= $_GET['action'] === 'add' ? "Added" : "Updated" ?>
                        </h1>
                        <p>
                            <?= $player->get_first_name() ?>
                            <?= $player->get_last_name() ?> (
                            <?= $player->get_nickname() ?>) Has Been Sucessfully
                            <?= $_GET['action'] === 'add' ? "Added To Your Team!" : "Updated!" ?>
                        </p>
                        <div class="player-add-card-container">
                            <div class="player-card">
                                <div class="top-box">
                                    <div class="top-menu">
                                    </div>
                                </div>
                                <div class="image-box">
                                    <img src="<?= file_exists("images/players-profile-images/{$player->get_nickname()}-profile-image.png") ? "images/players-profile-images/{$player->get_nickname()}-profile-image.png" : "images/players-profile-images/default-profile-image.webp" ?>"
                                        alt="profile-image">
                                </div>
                                <div class="image-box-country">
                                    <img src="<?= $player->get_country() === "Canada" ? "images/ca.png" : ($player->get_country() === "Brazil" ? "images/br.png" : ($player->get_country() === "Argentina" ? "images/ar.png" : ($player->get_country() === "Ecuador" ? "images/ec.png" : ($player->get_country() === "Peru" ? "images/pe.jpg" : ($player->get_country() === "United States" ? "images/us.png" : ($player->get_country() === "Poland" ? "images/pl.png" : "")))))) ?>"
                                        alt="country-flag">
                                    <p><i class='bx bx-map-pin'></i>
                                        <?= $player->get_city() ?>,
                                        <?= $player->get_country() === "Canada" ? "CA" : ($player->get_country() === "Brazil" ? "BR" : ($player->get_country() === "Argentina" ? "AR" : ($player->get_country() === "Ecuador" ? "EC" : ($player->get_country() === "Peru" ? "PE" : ($player->get_country() === "United States" ? "US" : ($player->get_country() === "Poland" ? "PL" : "")))))) ?>
                                    </p>
                                </div>
                                <div class="main-box">
                                    <div class="player-info">
                                        <span class="full-name">
                                            <?= $player->get_first_name() ?>
                                            <?= $player->get_last_name() ?>
                                        </span>
                                        <span class="nickname">-
                                            <?= $player->get_nickname() ?> -
                                        </span>
                                        <span class="professional">
                                            <?= $player->get_professional() == true ? "<i class='bx bx-check-circle'></i> Professional" : "<i class='bx bx-x-circle' ></i> Not A Professional" ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="nav">
                            <a class="action-button" href="./player-league.php?login=true"><i class='bx bx-arrow-back'></i>
                                Go Back To The Dashboard</a></button>
                        </div>
                    <?php endif; ?>
                <?php elseif ((!isset ($_GET['nickname']) || $_GET['nickname'] === "")): ?>
                    <h1>404 Not Found</h1>
                    <div class="nav">
                        <a class="action-button" href="./player-league.php?login=true"><i class='bx bx-arrow-back'></i>
                            Go Back To The Dashboard</a></button>
                    </div>
                <?php else: ?>
                    <h1>Player Not Found</h1>
                    <p>A Player With The Nickname
                        <?= htmlspecialchars($_GET['nickname']) ?> Has Not Been Found
                    </p>

                    <div class="nav">
                        <a class="action-button" href="./player-league.php?login=true"><i class='bx bx-arrow-back'></i>
                            Go Back To The Dashboard</a></button>
                    </div>
                <?php endif; ?>
            <?php elseif ($_GET['action'] === 'delete'): ?>
                <?php if (isset ($player)): ?>
                    <h1><i class='bx bx-check-circle'></i> Player Sucessfully Deleted</h1>
                    <p>
                        <?= $player->get_first_name() ?>
                        <?= $player->get_last_name() ?> (
                        <?= $player->get_nickname() ?>) Has Been Sucessfully Deleted From Your Team
                    </p>

                    <div class="player-add-card-container">
                        <div class="player-card">
                            <div class="top-box">
                                <div class="top-menu">
                                </div>
                            </div>
                            <div class="image-box">
                                <img src="<?= isset ($player_profile_image) ? 'data:image/png;base64,' . base64_encode($player_profile_image) : "images/players-profile-images/default-profile-image.webp" ?>"
                                    alt="profile-image">
                            </div>
                            <div class="image-box-country">
                                <img src="<?= $player->get_country() === "Canada" ? "images/ca.png" : ($player->get_country() === "Brazil" ? "images/br.png" : ($player->get_country() === "Argentina" ? "images/ar.png" : ($player->get_country() === "Ecuador" ? "images/ec.png" : ($player->get_country() === "Peru" ? "images/pe.jpg" : ($player->get_country() === "United States" ? "images/us.png" : ($player->get_country() === "Poland" ? "images/pl.png" : "")))))) ?>"
                                    alt="country-flag">
                                <p><i class='bx bx-map-pin'></i>
                                    <?= $player->get_city() ?>,
                                    <?= $player->get_country() === "Canada" ? "CA" : ($player->get_country() === "Brazil" ? "BR" : ($player->get_country() === "Argentina" ? "AR" : ($player->get_country() === "Ecuador" ? "EC" : ($player->get_country() === "Peru" ? "PE" : ($player->get_country() === "United States" ? "US" : ($$player->get_country() === "Poland" ? "PL" : "")))))) ?>
                                </p>
                            </div>
                            <div class="main-box">
                                <div class="player-info">
                                    <span class="full-name">
                                        <?= $player->get_first_name() ?>
                                        <?= $player->get_last_name() ?>
                                    </span>
                                    <span class="nickname">-
                                        <?= $player->get_nickname() ?> -
                                    </span>
                                    <span class="professional">
                                        <?= $player->get_professional() == true ? "<i class='bx bx-check-circle'></i> Professional" : "<i class='bx bx-x-circle' ></i> Not A Professional" ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="nav">
                        <a class="action-button" href="./player-league.php?login=true"><i class='bx bx-arrow-back'></i>
                            Go Back To The Dashboard</a></button>
                    </div>
                <?php elseif ((!isset ($_GET['nickname']) || $_GET['nickname'] === "")): ?>
                    <h1>404 Not Found</h1>
                    <div class="nav">
                        <a class="action-button" href="./player-league.php?login=true"><i class='bx bx-arrow-back'></i>
                            Go Back To The Dashboard</a></button>
                    </div>
                <?php else: ?>
                    <h1>Player Not Found</h1>
                    <p>A Player With The Nickname
                        <?= htmlspecialchars($_GET['nickname']) ?> Has Not Been Found
                    </p>

                    <div class="nav">
                        <a class="action-button" href="./player-league.php?login=true"><i class='bx bx-arrow-back'></i>
                            Go Back To The Dashboard</a></button>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <h1>404 Not Found</h1>
                <div class="nav">
                    <a class="action-button" href="./player-league.php?login=true"><i class='bx bx-arrow-back'></i>
                        Go Back To The Dashboard</a></button>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <h1>Welcome Back,
                <?= isset ($_SESSION['username']) ? $_SESSION['username'] : $leader_username ?>
            </h1>

            <div class="nav">
                <h2>
                    Dashboard
                </h2>

                <div class="action-button-container">
                    <a class="action-button" href="./player-league.php?login=true&action=add"><i class='bx bx-plus'></i> Add A
                        Player</a>
                    <a class="action-button" href="./player-league.php"><i class='bx bx-log-out'></i> Log Out</a>
                </div>
            </div>
            <p class="results">
                <?= count($players) > 0 ? count($players) . " Players" : "No Players Have Been Added Yet" ?>
            </p>
            <div class="player-card-container">
                <?php foreach ($players as $player): ?>
                    <div class="player-card">
                        <div class="top-box">
                            <div class="top-menu">
                                <div class="dropdown">
                                    <button class="dropbtn">
                                        <i class='bx bx-menu-alt-left'></i>
                                    </button>
                                    <div class="dropdown-content menu">
                                        <div class="menu-list">
                                            <a
                                                href="./player-league.php?login=true&action=update&nickname=<?= $player->get_nickname() ?>"><i
                                                    class='bx bx-edit'></i> Edit</a>
                                            <a
                                                href="./player-league.php?login=true&action=delete&nickname=<?= $player->get_nickname() ?>"><i
                                                    class='bx bx-trash'></i>Delete</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="image-box">
                            <img src="<?= file_exists("images/players-profile-images/{$player->get_nickname()}-profile-image.png") ? "images/players-profile-images/{$player->get_nickname()}-profile-image.png" : "images/players-profile-images/default-profile-image.webp" ?>"
                                alt="profile-image">
                        </div>
                        <div class="image-box-country">
                            <img src="<?= $player->get_country() === "Canada" ? "images/ca.png" : ($player->get_country() === "Brazil" ? "images/br.png" : ($player->get_country() === "Argentina" ? "images/ar.png" : ($player->get_country() === "Ecuador" ? "images/ec.png" : ($player->get_country() === "Peru" ? "images/pe.jpg" : ($player->get_country() === "United States" ? "images/us.png" : ($player->get_country() === "Poland" ? "images/pl.png" : "")))))) ?>"
                                alt="country-flag">
                            <p><i class='bx bx-map-pin'></i>
                                <?= $player->get_city() ?>,
                                <?= $player->get_country() === "Canada" ? "CA" : ($player->get_country() === "Brazil" ? "BR" : ($player->get_country() === "Argentina" ? "AR" : ($player->get_country() === "Ecuador" ? "EC" : ($player->get_country() === "Peru" ? "PE" : ($player->get_country() === "United States" ? "US" : ($player->get_country() === "Poland" ? "PL" : "")))))) ?>
                            </p>
                        </div>
                        <div class="main-box">
                            <div class="player-info">
                                <span class="full-name">
                                    <?= $player->get_first_name() ?>
                                    <?= $player->get_last_name() ?>
                                </span>
                                <span class="nickname">-
                                    <?= $player->get_nickname() ?> -
                                </span>
                                <span class="professional">
                                    <?= $player->get_professional() == true ? "<i class='bx bx-check-circle'></i> Professional" : "<i class='bx bx-x-circle' ></i> Not A Professional" ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</body>

</html>